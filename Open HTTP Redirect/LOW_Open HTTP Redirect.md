# Open HTTP Redirect 취약점 실습

## 개요
- 본 실습에서는 DVWA의 Low 단계에서 Open HTTP Redirect 취약점을 대상으로 `redirect` 파라미터 값에 대한  
  검증 없이 외부 URL로 리다이렉트가 가능한지 확인하여 비검증 리다이렉트 취약점 존재 여부를 점검했습니다.

- 주요정보통신기반시설 기술적 취약점 분석·평가 방법 상세가이드에  
  Open HTTP Redirect 독립 항목은 없으며, 프로세스 검증 누락(p.739) 항목과 연계하여 점검하였습니다.

---

## 주요정보통신기반시설 기준

> 출처 : https://www.kisa.or.kr/2060204/form?postSeq=22&page=1#fnPostAttachDownload ( p.739 ~ 743 )

### 점검 내용

- 서비스 제공에 필요한 사용자 입력 및 실행단계의 흐름에 대한 검증의 적절성 여부를 점검합니다.

---

### 점검 목적

- 서버 사이드에서 적절한 검증을 통해 비정상적인 입력 및 프로세스 흐름으로 허용되지 않은 기능에 접근하는 것을 차단하기 위함입니다.

---

### 보안 위협

- 프로세스 또는 기능에 대한 접근 제어 및 검증이 미흡할 경우, 비정상적인 입력값을 통해  
  사용자를 악성 외부 사이트로 유도하거나 중요 페이지에 대한 무단 접근 및 개인정보 유출이 가능합니다.

---

### 판단 기준

#### 양호

- 중요 페이지에 사용자 검증 로직이 구현되어 있어, 타 사용자의 권한 탈취 및 URL 직접 접근이 제한된 경우

#### 취약

- 중요 페이지에 사용자 검증 로직이 미흡하여, 타 사용자의 권한 탈취 및 URL 직접 접근이 가능한 경우

---

## 점검 절차

1. DVWA Open HTTP Redirect 페이지 접근 및 기본 화면 확인  
2. Quote 1, Quote 2 링크 클릭으로 정상 리다이렉트 동작 및 URL 파라미터 구조 확인  
3. `redirect` 파라미터 값을 외부 URL로 변조하여 리다이렉트 성공 여부 확인  
4. Burp Suite Intercept를 통해 변조 요청 및 리다이렉트 흐름 캡처  

---

## 취약점 검증 및 공격 수행

### 실습 화면 (기본 화면)

- DVWA Open HTTP Redirect 페이지로, Quote 1과 Quote 2 두 개의 링크가 제공됩니다.
- 각 링크 클릭 시 `redirect` 파라미터를 통해 내부 페이지로 이동하는 구조입니다.

<img src="/Open_HTTP_Redirect/img/01.png" width="100%">

---

### Quote 1 클릭 — 정상 리다이렉트 확인

- Quote 1 클릭 시 주소창이 `open_redirect/source/info.php?id=1`로 변경되며 내부 페이지로 이동합니다.
- `redirect` 파라미터가 `id=1`을 통해 내부 경로를 지정하는 구조임을 확인할 수 있습니다.

<img src="/Open_HTTP_Redirect/img/02.png" width="100%">

---

### Quote 2 클릭 — 정상 리다이렉트 확인

- Quote 2 클릭 시 주소창이 `open_redirect/source/info.php?id=2`로 변경되며 내부 페이지로 이동합니다.
- Low 레벨에서는 `redirect` 파라미터 값에 대한 검증이 전혀 없어 임의의 URL 삽입이 가능한 구조입니다.

<img src="/Open_HTTP_Redirect/img/03.png" width="100%">

---

### Burp Suite Intercept — 변조 요청 확인

- Burp Suite Intercept를 통해 `redirect` 파라미터에 외부 URL을 삽입한 요청을 캡처했습니다.

**Request**

- `GET /DVWA/vulnerabilities/open_redirect/source/low.php?redirect=https://www.google.com HTTP/1.1`
- `Cookie: security=low; PHPSESSID=24d7dcd12c53084a37362cc1328f2f87`

- 서버는 `redirect` 파라미터 값이 외부 URL임에도 검증 없이 `Location` 헤더에 그대로 삽입하여 리다이렉트를 수행합니다.

<img src="/Open_HTTP_Redirect/img/04.png" width="100%">

---

### Burp Suite Intercept — 정상 요청 비교

- Quote 2 클릭 시 발생하는 정상 요청을 캡처하여 변조 요청과 비교합니다.

**Request**
- `GET /DVWA/vulnerabilities/open_redirect/source/info.php?id=2 HTTP/1.1`
- `Cookie: security=low; PHPSESSID=24d7dcd12c53084a37362cc1328f2f87`

- 정상 요청은 내부 경로(`info.php?id=2`)로 이동하지만, 변조 요청은 외부 URL로 리다이렉트가 가능한 상태입니다.

<img src="/Open_HTTP_Redirect/img/05.png" width="100%">

---

### Burp Suite Intercept — 외부 리다이렉트 요청 확인

- `redirect=https://www.google.com` 요청 후 Burp Suite에서 실제 google.com으로의 요청이 인터셉트된 것을 확인할 수 있습니다.

**Request**
- `GET / HTTP/2`
- `Host: www.google.com`
- `Referer: http://192.168.11.36/`

- DVWA 서버를 경유하여 google.com으로 완전히 리다이렉트된 것이 확인됩니다.

<img src="/Open_HTTP_Redirect/img/06.png" width="100%">

---

### 외부 사이트 리다이렉트 성공 확인

- `redirect` 파라미터에 `https://www.google.com`을 삽입한 결과, 브라우저가 실제 google.com으로 이동했습니다.
- 주소창이 `www.google.com`으로 변경되어 외부 리다이렉트가 완전히 성공한 것을 확인할 수 있습니다.

<img src="/Open_HTTP_Redirect/img/07.png" width="100%">

---

### 소스코드 확인

- View Source를 통해 PHP 소스코드를 확인한 결과, `redirect` 파라미터 값에 대한 검증 로직이 전혀 존재하지 않습니다.

```php
  <?php
    if (array_key_exists ("redirect", $_GET) && $_GET['redirect'] != "") {
        header ("location: " . $_GET['redirect']);  // redirect 파라미터 값을 검증 없이 그대로 Location 헤더에 삽입
        exit;
    }
    
    http_response_code (500);
    ?>
    <p>Missing redirect target.</p>
    <?php
    exit;
  ?>
```

- `$_GET['redirect']` 값이 내부 URL인지 외부 URL인지 확인하지 않고 `Location` 헤더에 그대로 삽입합니다.
- 공백 여부만 체크할 뿐 도메인 검증, 화이트리스트 적용, URL 인코딩 처리가 전혀 없어 임의의 외부 URL 삽입이 가능합니다.

---

## 취약점 분석

- `redirect` 파라미터에 외부 URL을 삽입하는 것만으로 DVWA 서버를 경유하여 google.com으로 리다이렉트가 성공했습니다.
- 서버는 `redirect` 파라미터 값에 대한 검증 없이 `Location` 헤더에 그대로 사용하고 있어 임의의 외부 URL 삽입이 가능한 상태입니다.
- 실제 공격 시 신뢰할 수 있는 DVWA 도메인 링크를 피해자에게 전달하면, 피해자는 악성 외부 사이트로 유도됩니다.
- **판단 : 취약** — 실제 환경이었다면 피싱 사이트 유도, 자격증명 탈취, OAuth 토큰 탈취 등 2차 공격으로 이어질 수 있는 위험한 상태입니다.

---

## 대응 방안

### 화이트리스트 기반 URL 검증 구현

- 허용할 리다이렉트 대상 URL을 서버에서 관리하고, 목록에 없는 URL은 리다이렉트를 거부합니다.

```php
$allowed = ['info.php?id=1', 'info.php?id=2'];
if (in_array($_GET['redirect'], $allowed)) {
    header("location: " . $_GET['redirect']);
    exit;
}
```

- `$allowed` 배열에 허용할 URL만 미리 정의하고, `in_array()`로 파라미터 값이 배열 안에 있는지 확인합니다.
- `https://www.google.com` 같은 외부 URL은 배열에 없으므로 조건이 false가 되어 리다이렉트 자체가 실행되지 않습니다.

#### 핵심 효과
- 외부 URL 삽입 자체를 서버에서 차단하여 Open Redirect 공격을 방지할 수 있습니다.

---

### 인덱스 방식 리다이렉트 적용

- `redirect` 파라미터로 URL을 직접 받지 않고, 숫자 인덱스만 허용하여 서버에서 매핑된 URL로 이동시킵니다.

```php
  $urls = [1 => 'info.php?id=1', 2 => 'info.php?id=2'];
  $id = (int)$_GET['redirect'];
  if (isset($urls[$id])) {
      header("location: " . $urls[$id]);
      exit;
  }
```

- `(int)` 형변환으로 파라미터 값을 강제로 정수로 변환합니다.  
  `https://www.google.com`을 넣으면 `0`이 되어 `$urls[0]`이 존재하지 않으므로 리다이렉트가 실행되지 않습니다.
- 실제 리다이렉트에 사용되는 URL은 `$_GET['redirect']`가 아닌 서버가 정의한 `$urls` 배열의 값이므로, 사용자 입력이 URL에 직접 반영되지 않습니다.

#### 핵심 효과

- 사용자 입력이 URL에 직접 반영되지 않아 외부 URL 삽입 자체가 불가능합니다.

---

### 동일 도메인 여부 검증

- 리다이렉트 대상이 동일 도메인인지 검증하고, 외부 도메인으로의 리다이렉트를 차단합니다.

#### 핵심 효과

- URL 인코딩 우회나 프로토콜 생략 방식의 우회 시도도 함께 방어할 수 있습니다.
