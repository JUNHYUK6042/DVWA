# CSP Bypass 취약점 실습

## 개요
- 본 실습에서는 DVWA의 Low 단계에서 CSP (Content Security Policy) Bypass 취약점을 대상으로  
  과도하게 허용된 whitelist 도메인을 악용하여 악성 스크립트가 실행되는지 확인하여 CSP 우회 취약점 존재 여부를 점검했습니다.

- CSP Bypass는 XSS 방어 메커니즘인 Content Security Policy를 우회하는 공격 기법으로, 주요정보통신기반시설  
  기술적 취약점 분석·평가 방법 상세가이드의 크로스사이트 스크립트(XSS) 항목(p.711~714)과 연계하여 점검하였습니다.

---

## 주요정보통신기반시설 기준

> 출처 : https://www.kisa.or.kr/2060204/form?postSeq=22&page=1#fnPostAttachDownload ( p.711 ~ 714 )

### 점검 내용
- 웹 애플리케이션 내 악성 스크립트가 다른 사용자의 브라우저에서 실행되는 취약점 존재 여부를 점검합니다.

---

### 점검 목적
- 사용자 입력값에 대한 검증을 실시하여, 사용자 세션 탈취, 악성 코드 삽입 등의 악의적인 스크립트 실행을 차단하기 위해서입니다.

---

### 보안 위협
- CSP가 설정되어 있더라도 whitelist에 사용자 콘텐츠 업로드가 가능한 외부 도메인이 포함되어 있을 경우,  
  공격자는 해당 도메인에 악성 스크립트를 업로드하여 CSP를 우회하고 스크립트를 실행할 수 있습니다.
- 입력값에 대한 검증 없이 외부 스크립트 URL을 그대로 삽입할 경우,  
  사용자의 쿠키(세션) 탈취, 피싱 사이트 유도 등의 악의적인 공격으로 이어질 수 있습니다.

---

### 판단 기준

#### 양호
- CSP whitelist가 최소 권한 원칙에 따라 구성되어 있으며, 입력값에 대한 검증이 이루어져 악의적인 스크립트가 실행되지 않는 경우

#### 취약
- CSP whitelist에 불필요한 외부 도메인이 과도하게 허용되어 있거나, 입력값 검증 없이 외부 스크립트가 실행되는 경우

---

## 점검 절차

1. 실습 화면 및 소스코드 확인
2. 입력값 무검증으로 인한 script 태그 삽입 확인
3. whitelist 도메인(cdn.jsdelivr.net) 악용을 통한 CSP 우회 확인
4. 서버 로컬 파일(`'self'`)을 이용한 쿠키 탈취 스크립트 실행 확인

---

## 취약점 검증 및 공격 수행

### 실습 화면
- DVWA의 CSP Bypass 화면이며, 외부 스크립트 URL을 입력하면 해당 스크립트를 페이지에 포함시키는 구조입니다.

<img src="/CSP%20Bypass/img/01.png" width="100%">

---

### 소스코드 확인
- View Source를 통해 PHP 소스코드를 확인한 결과, 아래 두 가지 취약점이 존재합니다.

```php
$headerCSP = "Content-Security-Policy: script-src 'self' https://pastebin.com hastebin.com 
www.toptal.com example.com code.jquery.com https://ssl.google-analytics.com 
unpkg.com cdn.jsdelivr.net digi.ninja ;";
```

```php
if (isset ($_POST['include'])) {
    $page['body'] .= "<script src='" . $_POST['include'] . "'></script>";
}
```

- **첫째**, CSP whitelist에 cdn.jsdelivr.net 등 사용자가 임의의 콘텐츠를 업로드할 수 있는 도메인이 과도하게 포함되어 있습니다.
- **둘째**, POST 파라미터 `include` 값을 아무런 검증 없이 `<script src>` 태그에 직접 삽입하고 있습니다.

---

### 입력값 무검증으로 인한 script 태그 삽입 확인

- 입력값이 검증 없이 HTML에 직접 삽입되는지 확인하기 위해 whitelist에 포함된 도메인 URL을 입력했습니다.

```
https://ssl.google-analytics.com
```

<img src="/CSP%20Bypass/img/02.png" width="100%">

- Burp Suite HTTP history의 Response 탭에서 실제로 생성된 HTML을 확인한 결과,  
  입력값이 아래와 같이 `<script src>` 태그에 그대로 삽입된 것을 확인할 수 있습니다.

```html
<script src='https://ssl.google-analytics.com'></script>
```

<img src="/CSP%20Bypass/img/03.png" width="100%">

- 입력값에 대한 서버사이드 검증이 전혀 없으며, 사용자가 입력한 URL이 그대로 스크립트 소스로 사용되고 있음을 확인하였습니다.

---

### cdn.jsdelivr.net 도메인 악용을 통한 CSP 우회

- whitelist에 허용된 `cdn.jsdelivr.net` 도메인을 악용하여 CSP 우회가 가능한지 확인하기 위해 아래 URL을 입력했습니다.

```
https://cdn.jsdelivr.net/gh/digininja/csp_bypass/alert.js
```

<img src="/CSP%20Bypass/img/04.png" width="100%">

- Include 클릭 결과 `CSP Bypassed` 문자열이 담긴 alert 팝업이 실행되는 것을 확인할 수 있습니다.

<img src="/CSP%20Bypass/img/05.png" width="100%">

- `cdn.jsdelivr.net`은 GitHub 저장소의 파일을 CDN으로 서빙하는 서비스로, 누구나 임의의 JS 파일을 GitHub에 올리고 해당 CDN URL로 서빙할 수 있습니다.
- CSP가 `cdn.jsdelivr.net`을 신뢰된 도메인으로 선언했기 때문에 브라우저는 해당 도메인에서 오는 스크립트를 무조건 실행합니다.
- 이를 통해 **whitelist에 사용자 콘텐츠 업로드가 가능한 CDN 도메인이 포함될 경우 CSP가 무력화됨**을 확인하였습니다.

---

### 서버 로컬 파일(`'self'`)을 이용한 쿠키 탈취 확인

- `'self'` 정책을 악용하여 서버에 업로드한 악성 JS 파일을 통해 쿠키 탈취가 가능한지 확인하기 위해  
  서버에 아래 내용의 JS 파일(`/var/www/html/csp_low.js`)을 생성하였습니다.

```javascript
alert(document.cookie);
```

<img src="/CSP%20Bypass/img/06.png" width="100%">

- 해당 파일의 URL을 입력창에 입력했습니다.

```
http://192.168.11.36/csp_low.js
```

<img src="/CSP%20Bypass/img/07.png" width="100%">

- 실행 결과 현재 세션의 쿠키 정보인  
  `security=low; PHPSESSID=af861ea3281960ec91db60f6fecb7933` 값이 alert 팝업으로 출력되는 것을 확인할 수 있습니다.

<img src="/CSP%20Bypass/img/08.png" width="100%">

- `'self'`는 동일 출처의 스크립트를 허용하는 설정으로, 서버에 파일 업로드 권한을 가진 공격자가 악성 JS를 서버에 올릴 경우 CSP를 우회하여 쿠키 탈취가 가능합니다.
- 실제 공격 환경에서는 `alert` 대신 공격자 서버로 쿠키를 전송하는 코드를 사용하여 세션 하이재킹 공격으로 이어질 수 있습니다.

---

## 취약점 분석

- 입력값에 대한 검증 없이 외부 URL이 `<script src>` 태그에 직접 삽입되는 것을 확인했습니다.
- CSP whitelist에 사용자 콘텐츠 업로드가 가능한 도메인(cdn.jsdelivr.net)이 포함되어 있어 CSP가 무력화되었습니다.
- `'self'` 정책을 통해 서버 로컬 JS 파일로도 쿠키 탈취 스크립트 실행이 가능한 상태입니다.
- **판단 : 취약** — 실제 환경이었다면 CSP가 존재함에도 불구하고 세션 탈취, 악성 스크립트 실행으로 이어질 수 있는 위험한 상태입니다.

---

## 대응 방안

### CSP whitelist 최소화

- CSP whitelist를 `'self'`만으로 제한하거나, 실제로 필요한 도메인만 최소한으로 허용합니다.
- 특히 cdn.jsdelivr.net, unpkg.com 등 사용자가 임의 콘텐츠를 업로드할 수 있는 도메인은 whitelist에서 반드시 제거해야 합니다.

#### 핵심 효과
- 공격자가 신뢰된 도메인을 악용하여 악성 스크립트를 실행하는 경로를 차단할 수 있습니다.

---

### 입력값 검증 및 허용 URL 서버사이드 검증

- 사용자로부터 입력받은 URL을 그대로 사용하지 않고, 서버사이드에서 허용된 도메인 목록과 비교하여 검증합니다.

```php
$allowed_domains = ['192.168.11.36'];
$parsed = parse_url($_POST['include']);
if (!in_array($parsed['host'], $allowed_domains)) {
    die('허용되지 않은 URL입니다.');
}
```

#### 핵심 효과
- whitelist에 없는 도메인의 스크립트 삽입 시도를 서버 단에서 사전 차단할 수 있습니다.

---

### nonce 또는 hash 기반 CSP 적용

- 도메인 기반 whitelist 대신 nonce(일회성 토큰) 또는 hash 방식을 사용하여 허용된 스크립트만 실행되도록 제한합니다.

```
Content-Security-Policy: script-src 'nonce-랜덤값';
```

#### 핵심 효과
- 외부 도메인 전체를 신뢰하는 방식이 아니라 개별 스크립트 단위로 허용 여부를 제어하여 우회 공격을 차단할 수 있습니다.

---

### 쿠키 보안 옵션 설정

- 세션 탈취 방지를 위해 쿠키에 `HttpOnly`, `Secure`, `SameSite` 옵션을 설정하여 쿠키가 JavaScript로 접근되지 않도록 보호합니다.

#### 핵심 효과
- CSP 우회 취약점이 존재하더라도 쿠키 탈취를 통한 세션 하이재킹을 방지할 수 있습니다.
