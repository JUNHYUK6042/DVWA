# Insecure CAPTCHA 취약점

## 개요

- 본 실습에서는 DVWA의 Low 단계에서 Insecure CAPTCHA 취약점을 대상으로  
  Burp Suite Repeater를 통해 step 파라미터를 조작하여 CAPTCHA 검증 단계를 우회하고,  
  실제 비밀번호 변경이 이루어지는지 확인하여 프로세스 검증 누락 취약점 존재 여부를 점검했습니다.

- Insecure CAPTCHA는 CAPTCHA 검증이 step=1 단계에서만 수행되고 step=2에서는 재검증 없이 비밀번호 변경이 실행되는 구조로,  
  공격자가 step 파라미터를 조작하여 CAPTCHA를 전혀 풀지 않고도 중요 기능에 접근할 수 있는 취약점입니다. 

- CWE-306(Missing Authentication for Critical Function)에 해당하고  
  주요정보통신기반시설 기술적 취약점 분석·평가 방법 상세가이드의 프로세스 검증 누락(p.739) 항목과 연계하여 점검하였습니다.

---

## 주요정보통신기반시설 기준

> 출처 : https://www.kisa.or.kr/2060204/form?postSeq=22&page=1#fnPostAttachDownload ( p.739~743 )

### 점검 내용

- 서비스 제공에 필요한 사용자 입력 및 실행단계의 흐름에 대한 검증의 적절성 여부를 점검합니다.

---

### 점검 목적

- 서버 사이드에서 단계별 검증을 통해 클라이언트 파라미터 조작으로 인한 프로세스 우회를 차단합니다.

---

### 보안 위협

- CAPTCHA 검증이 step=1에서만 수행되고 step=2에서는 재검증이 없는 경우,  
  공격자가 step 파라미터를 직접 조작하여 CAPTCHA 없이 비밀번호 변경 등 중요 기능을 수행할 수 있습니다.
- 서버가 프로세스 흐름을 클라이언트 파라미터에 의존하는 구조에서는 단계 우회를 통한 자동화 공격(봇)이 가능합니다.

---

### 판단 기준

#### 양호

- CAPTCHA 검증 결과가 서버 세션에 저장되어 있으며, step=2 단계에서 세션을 통해 CAPTCHA 통과 여부를 재확인하는 경우

#### 취약

- step=2 단계에서 CAPTCHA 재검증이 없으며, 클라이언트가 step 파라미터를 조작하여 CAPTCHA 검증 단계를 건너뛸 수 있는 경우

---

## 점검 절차

1. DVWA Insecure CAPTCHA 초기 화면 및 동작 확인
2. 정상 흐름으로 CAPTCHA를 풀고 비밀번호 변경 성공 확인
3. Burp Suite HTTP History에서 step=1, step=2 패킷 구조 분석
4. step=2 패킷을 Burp Suite Repeater로 전송
5. password_new, password_conf 파라미터를 원하는 값으로 변조 후 Send
6. CAPTCHA 없이 비밀번호 변경 성공 확인
7. 변경된 비밀번호로 로그인 성공 확인

---

## 취약점 검증 및 공격 수행

### 실습 화면

- DVWA의 Insecure CAPTCHA 페이지로, 비밀번호 변경 폼과 reCAPTCHA 체크박스가 함께 제공됩니다.
- 정상 흐름에서는 reCAPTCHA를 통과해야 비밀번호를 변경할 수 있는 구조입니다.

<img src="/Insecure%20Captcha/img/01.png" width="100%">

---

### 정상 흐름 확인 — CAPTCHA 풀고 비밀번호 변경 성공

- Burp Suite Intercept OFF 상태에서 reCAPTCHA 체크박스를 클릭하고 Change 버튼을 클릭합니다.
- "Password Changed." 메시지가 출력되며 정상적으로 비밀번호가 변경됩니다.
- 이 흐름에서 서버는 step=1(CAPTCHA 검증) → step=2(비밀번호 변경) 순서로 처리합니다.

<img src="/Insecure%20Captcha/img/02.png" width="100%">

---

### Burp Suite HTTP History — step=1 패킷 확인

- HTTP History에서 정상 흐름의 첫 번째 요청을 보면, step=1과 함께 g-recaptcha-response 토큰이 전송됩니다.
- 이 단계에서 서버는 구글 reCAPTCHA API에 토큰을 검증 요청하고, 통과 시 step=2 폼을 제공합니다.

```
step=1&password_new=insecure&password_conf=insecure&g-recaptcha-response=...&Change=Change
```

<img src="/Insecure%20Captcha/img/03.png" width="100%">

---

### Burp Suite HTTP History — step=2 패킷 확인

- step=1 통과 후 전송된 두 번째 요청을 보면, step=2와 비밀번호 파라미터만 전송됩니다.
- **CAPTCHA 관련 파라미터가 전혀 포함되지 않으며**, 서버는 step=2 요청을 받으면 재검증 없이 비밀번호를 변경합니다.

```
step=2&password_new=insecure&password_conf=insecure&Change=Change
```

<img src="/Insecure%20Captcha/img/04.png" width="100%">

---

### Burp Suite Repeater — step=2 패킷 전송

- HTTP History에서 step=2 요청을 우클릭 → Send to Repeater로 전송합니다.
- Repeater에서 step=2 패킷을 그대로 볼 수 있으며, 이 패킷에는 CAPTCHA 검증 정보가 없습니다.
- 이 패킷만으로 서버에 요청을 보내면 CAPTCHA 없이도 비밀번호 변경이 가능한지 검증합니다.

<img src="/Insecure%20Captcha/img/05.png" width="100%">

---

### Burp Suite Repeater — 비밀번호 변조 후 Send

- Repeater에서 password_new와 password_conf 값을 `hacker`로 변조한 후 Send 버튼을 클릭합니다.
- Response에서 HTTP/1.1 200 OK가 반환되어 요청이 정상 처리되었습니다.

```
step=2&password_new=hacker&password_conf=hacker&Change=Change
```

<img src="/Insecure%20Captcha/img/06.png" width="100%">

---

### 변경된 비밀번호로 로그인 성공 확인

- DVWA에서 로그아웃 후 username: admin, password: hacker로 로그인을 시도합니다.
- HTTP History에서 로그인 요청이 302 Found로 리다이렉트된 것을 볼 수 있습니다.
- 이는 로그인이 성공하여 index.php로 이동한 것으로, CAPTCHA를 전혀 거치지 않고 비밀번호가 실제로 변경되었음을 증명합니다.

<img src="/Insecure%20Captcha/img/07.png" width="100%">

---

### DVWA 로그인 성공 화면

- 변경된 비밀번호 `hacker`로 DVWA 로그인에 성공하여 홈 화면이 출력됩니다.
- CAPTCHA 우회를 통한 비밀번호 변경 공격이 성공적으로 수행되었습니다.

<img src="/Insecure%20Captcha/img/08.png" width="100%">

---

## 취약점 분석

- step=1 블록에서는 `recaptcha_check_answer()`로 CAPTCHA를 검증하지만, step=2 블록에는 동일한 검증이 없습니다.
- step 파라미터는 클라이언트가 POST로 전달하는 값이므로 Burp Suite로 자유롭게 조작할 수 있습니다.
- 서버가 step 값을 신뢰하기 때문에, 공격자가 step=2를 직접 전송하면 CAPTCHA를 전혀 풀지 않아도 비밀번호 변경이 실행됩니다.

```php
if( isset( $_POST[ 'Change' ] ) && ( $_POST[ 'step' ] == '2' ) ) {
    // CAPTCHA 재검증 없음 — step=2 조건만 충족하면 바로 비밀번호 변경 실행
    $pass_new  = $_POST[ 'password_new' ];
    $pass_conf = $_POST[ 'password_conf' ];
    if( $pass_new == $pass_conf ) {
        // 비밀번호 변경 처리
    }
}
```

**판단 : 취약** — 실제 환경이었다면 자동화 봇이 CAPTCHA 없이 대량으로 비밀번호 변경, 계정 탈취 등을 수행할 수 있는 위험한 상태입니다.

---

## 대응 방안

### 서버 세션 기반 CAPTCHA 검증 및 프로세스 흐름 관리

- step=1에서 CAPTCHA 검증 성공 시 결과를 서버 세션에 저장하고, step=2에서 세션을 통해 CAPTCHA 통과 여부를 재확인합니다.
- step 파라미터는 클라이언트가 자유롭게 조작할 수 있으므로,  
  프로세스 진행 단계의 기준을 클라이언트 파라미터가 아닌 서버 세션으로 관리합니다.
- CAPTCHA 검증 결과를 일회성으로 처리하여 동일 세션으로 반복 요청이 불가능하도록 합니다.

```php
session_start(); // PHP 세션 초기화 — 서버가 사용자별 데이터를 저장하는 공간 생성

// step=1: 사용자가 CAPTCHA를 풀고 Change 버튼을 클릭한 첫 번째 요청
if( isset( $_POST['Change'] ) && $_POST['step'] == '1' ) {

    // 구글 reCAPTCHA 서버에 직접 검증 요청
    // 클라이언트가 전달한 값을 그대로 신뢰하지 않고 서버가 구글 API를 통해 직접 확인
    $resp = recaptcha_check_answer(
        $_DVWA['recaptcha_private_key'],      // 서버에만 존재하는 개인 키 — 클라이언트에 노출 불가
        $_POST['g-recaptcha-response']         // 사용자가 CAPTCHA를 풀었을 때 구글이 발급한 토큰
    );

    if( $resp ) {
        // CAPTCHA 검증 성공 시 서버 세션에 통과 기록 저장
        // 세션은 서버에서만 관리되므로 클라이언트가 Burp Suite로 조작 불가
        $_SESSION['captcha_passed'] = true;
        // step=2 폼 제공
    } else {
        echo "<pre>The CAPTCHA was incorrect. Please try again.</pre>";
    }
}

// step=2: 실제 비밀번호 변경 요청
if( isset( $_POST['Change'] ) && $_POST['step'] == '2' ) {

    // 클라이언트 파라미터가 아닌 서버 세션 기준으로 CAPTCHA 통과 여부 재확인
    // 공격자가 step=2를 직접 전송한 경우 세션에 기록이 없으므로 여기서 차단됨
    if( !isset( $_SESSION['captcha_passed'] ) || $_SESSION['captcha_passed'] !== true ) {
        echo "<pre>CAPTCHA verification required.</pre>";
        exit; // 비밀번호 변경 처리 중단
    }

    // 일회성 처리 — 세션 기록 삭제하여 동일 세션으로 반복 비밀번호 변경 시도 차단
    unset( $_SESSION['captcha_passed'] );

    // 비밀번호 변경 처리
}
```

#### 핵심 효과

- step=2 패킷을 직접 전송하거나 step 파라미터를 조작하더라도  
  세션에 CAPTCHA 통과 기록이 없으면 요청이 거부되어 단계 우회가 불가능합니다.

---

### 중요 기능에 매 요청마다 서버사이드 CAPTCHA 검증 수행

- 비밀번호 변경, 계정 생성 등 중요 기능은 단계를 나누지 않고  
  하나의 요청에서 CAPTCHA 검증과 기능 수행을 함께 처리합니다.
- 단계 분리 구조 자체를 제거하면 step 파라미터 조작을 통한 우회 경로가 원천 차단됩니다.

```php
if( isset( $_POST['Change'] ) ) {

    // 단계를 나누지 않고 하나의 요청에서 CAPTCHA 검증 + 비밀번호 변경 동시 처리
    // step 파라미터 자체가 없으므로 step 조작 공격 경로가 구조적으로 존재하지 않음
    $resp = recaptcha_check_answer(
        $_DVWA['recaptcha_private_key'],
        $_POST['g-recaptcha-response']  // 매 요청마다 새로운 CAPTCHA 토큰 필요
    );

    if( !$resp ) {
        // CAPTCHA 실패 시 비밀번호 변경 처리 자체를 중단
        echo "<pre>The CAPTCHA was incorrect. Please try again.</pre>";
        exit;
    }

    // CAPTCHA를 통과한 경우에만 비밀번호 변경 처리 실행
}
```

#### 핵심 효과

- step 파라미터 자체가 없어 단계 우회 공격이 구조적으로 불가능합니다.

---

### 비정상 요청 탐지 및 로깅

- CAPTCHA 검증 없이 step=2로 직접 요청하거나,  
  세션에 CAPTCHA 통과 기록이 없는 요청을 로그로 기록하여 자동화 공격을 탐지합니다.
- IP, 요청 파라미터, 시각을 로그에 포함하여 이상 패턴을 조기에 식별합니다.

```php
if( !isset( $_SESSION['captcha_passed'] ) || $_SESSION['captcha_passed'] !== true ) {

    // error_log()는 서버의 PHP 에러 로그 파일에 기록 — 공격자는 접근 불가
    error_log(
        "[Insecure CAPTCHA] CAPTCHA 우회 시도 탐지 | " .
        "IP: " . $_SERVER['REMOTE_ADDR'] .               // 요청한 클라이언트 IP 주소
        " | step: " . htmlspecialchars($_POST['step']) .  // 전달된 step 값 (htmlspecialchars로 XSS 방지)
        " | time: " . date('Y-m-d H:i:s')                // 시도 시각
    );

    echo "<pre>CAPTCHA verification required.</pre>";
    exit;
}
```

#### 핵심 효과

- CAPTCHA를 우회한 비정상 요청을 조기에 탐지하고, 반복 시도 발생 시 IP 차단 등 추가 대응이 가능합니다.
