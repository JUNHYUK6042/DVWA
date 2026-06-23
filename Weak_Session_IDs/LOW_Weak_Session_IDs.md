# Weak Session IDs 취약점

---

## 개요

- 본 실습에서는 DVWA의 Low 단계에서 Weak Session IDs 취약점을 대상으로  
  세션 ID가 예측 가능한 순차 증가 방식으로 발급되어 공격자가 타 사용자의 세션 ID를 추측하고  
  세션 하이재킹이 가능한지 확인하여 취약한 세션 관리 여부를 점검했습니다.

- 주요정보통신기반시설 기술적 취약점 분석·평가 방법 상세가이드  
  **16. 불충분한 세션 관리(p.759)** 항목과 연계하여 점검하였습니다.

---

## 주요정보통신기반시설 기준

> 출처 : https://www.kisa.or.kr/2060204/form?postSeq=22&page=1#fnPostAttachDownload ( p.759~762 )

### 점검 내용

- 세션 만료 기간 설정, 예측 가능한 세션 ID 생성, 고정된 세션 ID 발행 등 세션 관리 정책을 점검합니다.

---

### 점검 목적

- 사용자의 세션 ID를 적절히 관리하여 공격자가 불법적으로 접근하거나 비인가적인 세션 탈취를 차단합니다.

---

### 보안 위협

- 사용자에게 발급되는 세션 ID가 만료되지 않거나, 고정 및 예측 가능한 형태일 경우,  
  공격자는 해당 세션 ID를 탈취하여 타 사용자나 시스템에 무단 접근할 수 있으며,  
  이로 인해 중요 데이터의 무결성이 훼손될 수 있습니다.

---

### 판단 기준

#### 양호

- 추측 불가능한 세션 ID가 발급되고, 세션 종료 시간이 설정되어 있는 경우

#### 취약

- 세션 ID가 일정한 패턴으로 발급되거나 세션 종료 시간이 설정되지 않아 세션 재사용이 가능한 경우

---

## 점검 절차

1. DVWA Weak Session IDs 페이지 접근 및 기본 화면 확인
2. Burp Suite HTTP history를 통해 Generate 버튼 클릭 시 발급되는 세션 ID 패턴 분석
3. 반복 클릭으로 dvwaSession 쿠키가 순차 증가하는지 확인
4. 소스코드를 통해 취약한 세션 ID 생성 로직 확인

---

## 취약점 검증 및 공격 수행

### 실습 화면 (기본 화면)

- DVWA Weak Session IDs 페이지로, Generate 버튼 클릭 시 `dvwaSession`이라는 쿠키를 새로 발급합니다.
- 페이지 안내 문구 "This page will set a new cookie called dvwaSession each time the button is clicked."에서 버튼 클릭마다 새로운 세션 ID가 발급되는 구조임을 알 수 있습니다.

<img src="/Weak_Session_IDs/img/01.png" width="100%">

---

### Burp Suite HTTP history 확인 (dvwaSession=1)

- Generate 버튼을 처음 클릭하면 서버가 `Set-Cookie: dvwaSession=1`을 응답합니다.

**Request 탭**
- `POST /DVWA/vulnerabilities/weak_id/`
- `Cookie: security=low; PHPSESSID=24d7dcd12c53084a37362cc1328f2f87`

**Response 탭**
- `HTTP/1.1 200 OK`
- `Set-Cookie: dvwaSession=1`

<img src="/Weak_Session_IDs/img/02.png" width="100%">

---

### Burp Suite HTTP history 확인 (dvwaSession=2)

- 두 번째 클릭 시 Request의 쿠키에 `dvwaSession=1`이 포함되어 있으며, 서버는 `Set-Cookie: dvwaSession=2`를 응답합니다.
- 세션 ID가 1에서 2로 순차 증가하는 패턴이 확인됩니다.

**Request 탭**
- `POST /DVWA/vulnerabilities/weak_id/`
- `Cookie: dvwaSession=1; security=low; PHPSESSID=24d7dcd12c53084a37362cc1328f2f87`

**Response 탭**
- `HTTP/1.1 200 OK`
- `Set-Cookie: dvwaSession=2`

<img src="/Weak_Session_IDs/img/03.png" width="100%">

---

### Burp Suite HTTP history 확인 (dvwaSession=3)

- 세 번째 클릭 시 동일하게 `Set-Cookie: dvwaSession=3`이 응답됩니다.
- dvwaSession 쿠키가 1 → 2 → 3 으로 1씩 증가하는 패턴이 명확하게 확인됩니다.
- 공격자가 자신의 세션 ID를 기준으로 앞뒤 값을 추측하면 타 사용자의 세션 ID를 알아낼 수 있습니다.

**Request 탭**
- `POST /DVWA/vulnerabilities/weak_id/`
- `Cookie: dvwaSession=2; security=low; PHPSESSID=24d7dcd12c53084a37362cc1328f2f87`

**Response 탭**
- `HTTP/1.1 200 OK`
- `Set-Cookie: dvwaSession=3`

<img src="/Weak_Session_IDs/img/04.png" width="100%">

---

### 소스코드 확인

- Low 레벨 소스코드에서 세션 ID 생성 로직이 단순 순차 증가 방식으로 구현되어 있습니다.

```php
<?php

$html = "";

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    if (!isset($_SESSION['last_session_id'])) {
        $_SESSION['last_session_id'] = 0;
    }
    $_SESSION['last_session_id']++;            // 세션 ID를 1씩 증가
    $cookie_value = $_SESSION['last_session_id'];
    setcookie("dvwaSession", $cookie_value);   // 증가된 값을 그대로 쿠키로 발급
}
?>
```

- `last_session_id` 값을 단순히 1씩 증가시켜 dvwaSession 쿠키로 발급합니다.
- 암호학적 난수 생성 없이 예측 가능한 정수 값을 세션 ID로 사용하고 있어 취약합니다.

---

## 취약점 분석

- Generate 버튼을 클릭할 때마다 dvwaSession 쿠키가 1 → 2 → 3 순으로 순차 증가하는 것을 Burp Suite HTTP history에서 확인했습니다.
- 세션 ID 생성에 난수가 사용되지 않아 공격자가 패턴만 파악하면 타 사용자의 세션 ID를 추측할 수 있습니다.
- 쿠키 값을 변조하면 인증 없이 해당 세션의 사용자로 접근이 가능한 상태입니다.
  
**판단 : 취약** — 실제 환경이었다면 세션 하이재킹을 통해 타 사용자 계정 탈취, 개인정보 유출, 권한 상승 공격으로 이어질 수 있습니다.

---

## 대응 방안

### 암호학적으로 안전한 난수 기반 세션 ID 생성

- 세션 ID는 예측 불가능한 암호학적 난수 생성기(CSPRNG)를 사용하여 생성합니다.
- PHP의 경우 `session_regenerate_id(true)`를 사용하여 로그인 시 새로운 세션 ID를 발급합니다.

```php
session_start();
session_regenerate_id(true); // 기존 세션 폐기 후 새 세션 ID 발급
```

#### 핵심 효과

- 세션 ID가 충분한 엔트로피를 가져 패턴 분석 및 브루트포스 공격을 방어할 수 있습니다.

---

### 세션 ID 128비트 이상 길이 적용

- OWASP Session Management Cheat Sheet는 세션 ID 길이의 최솟값을 **128비트(16바이트)**로 명시합니다.
- 짧거나 단순한 정수 값은 세션 ID로 사용해서는 안 됩니다.

#### 핵심 효과

- 세션 ID 탈취를 위한 브루트포스 공격을 현실적으로 불가능합니다.

---

### 쿠키 보안 옵션 설정

- dvwaSession 쿠키에 `HttpOnly`, `Secure`, `SameSite` 옵션을 설정하여 클라이언트 측 탈취를 방지합니다.

```php
setcookie("dvwaSession", $cookie_value, [
    'httponly' => true,
    'secure'   => true,
    'samesite' => 'Strict'
]);
```

#### 핵심 효과

- JavaScript를 통한 쿠키 탈취(XSS 연계 공격)와 네트워크 도청을 방지할 수 있습니다.

---

### 세션 만료 시간 설정

- 일정 시간 미사용 시 세션을 자동으로 만료시켜 장기간 유효한 세션이 탈취되는 위험을 줄입니다.

#### 핵심 효과

- 탈취된 세션 ID의 유효 시간을 제한하여 피해를 최소화할 수 있습니다.
