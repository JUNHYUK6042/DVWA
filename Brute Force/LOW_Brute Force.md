# Brute Force 취약점

## 개요

- 본 실습에서는 DVWA의 Low 단계에서 Brute Force 취약점을 대상으로 로그인 실패 횟수 제한 및  
  계정 잠금 정책 미적용으로 인해 자동화 도구를 이용한 무차별 대입 공격이 가능한지 확인하여 Brute Force 취약점 존재 여부를 점검했습니다.

- 주요정보통신기반시설 기술적 취약점 분석·평가 방법 상세가이드의 계정 잠금 임계값 설정 관련 항목(U-03)과 연계하여 점검하였습니다.

---

## 주요정보통신기반시설 기준

> 출처 : https://www.kisa.or.kr/2060204/form?postSeq=22&page=1#fnPostAttachDownload

### 점검 내용

- 사용자 계정 로그인 실패 시 계정 잠금 임계값 설정 여부 점검합니다.

---

### 점검 목적

- 무차별 대입 공격(Brute Force Attack)에 대응하여 시스템 자원을 보호하고 비인가 접근을 차단합니다.
- 자동화 도구를 이용한 반복 인증 시도로 인한 계정 탈취를 방지합니다.

---

### 보안 위협

- 로그인 시도 제한이 없을 경우 공격자는 무제한 인증 시도가 가능합니다.
- 비밀번호가 일치할 때까지 반복 공격 수행이 가능합니다.
- 계정 탈취 및 정보 유출 가능성이 증가합니다.

---

### 판단 기준

#### 양호

- 로그인 실패 횟수 10회 이하 제한이 적용된 경우

#### 취약

- 로그인 실패 횟수 제한이 없거나 10회를 초과하는 경우

---

## 점검 절차

1. 로그인 기능 존재 여부 확인
2. 동일 계정으로 반복 로그인 시도 수행
3. 로그인 실패 횟수 증가에 따른 시스템 반응 확인
4. 특정 횟수 초과 시 계정 잠금 여부 확인
5. 추가 인증(CAPTCHA, MFA 등) 적용 여부 확인

---

## 취약점 검증 및 공격 수행

### 로그인 요청 캡처

- DVWA 로그인 페이지에서 Username은 `admin`, Password는 `aaaaaa` 임의의 값 입력 후 로그인 시도합니다.
- Burp Suite Proxy → HTTP History에서 요청을 확인합니다.

<img src="/Brute%20Force/img/01.png" width="100%">

```
GET /DVWA/vulnerabilities/brute/?username=admin&password=aaaaa&Login=Login
```

- GET 방식으로 인증 정보가 전달되며 Username / Password 파라미터가 존재합니다.

<img src="/Brute%20Force/img/02.png" width="100%">

- 해당 요청을 우클릭 후 `Send to Intruder`를 클릭하여 전송합니다.

---

### Intruder 설정

- 다음 화면은 Burp Suite에서 Intruder 기능 화면입니다.

<img src="/Brute%20Force/img/03.png" width="100%">

- password 파라미터를 공격 위치로 지정하여, Position에서 `Add` 버튼을 클릭하면  
  오른쪽에 Payloads 입력 칸이 나옵니다.

---

### Payload 설정

- Payload type: Simple list
- password.lst 파일 로드 (/usr/share/john/password.lst)

<img src="/Brute%20Force/img/04.png" width="100%">

- Payload Configuration에서 `/usr/share/john/password.lst` 파일을 업로드합니다.

---

### 공격 수행

- Intruder 화면에서 Start Attack 버튼을 클릭하여 공격을 수행합니다.

<img src="/Brute%20Force/img/05.png" width="100%">

- 대부분 동일한 응답이 나오며, 특정 요청에서 응답 길이 변화가 발생합니다.

---

### 응답 분석

- 응답 길이 및 내용을 비교합니다.

<img src="/Brute%20Force/img/06.png" width="100%">

- 대부분의 응답 길이가 동일하게 나타났지만, 특정 payload에서만 응답 길이가 다른 것을 확인했습니다.
- 응답 길이가 다른 payload를 확인한 결과, 해당 값이 정상 로그인과 관련된 것으로 추정됩니다.

---

### 공격 성공 확인

- 로그인 화면에서 `admin`/`password` 값을 입력하여 로그인 시도합니다.

<img src="/Brute%20Force/img/07.png" width="100%">

#### 결과

```
"Welcome to the password protected area admin"
```

- Payload에서 확인한 값으로 정상 로그인에 성공하면서 응답 내용이 달라진 것을 확인했습니다.
- 관리자 계정(admin)의 비밀번호가 `password`임을 확인했습니다.

---

## 취약점 분석

- 해당 시스템은 로그인 실패 횟수에 대한 제한이나 계정 잠금 정책이 적용되어 있지 않습니다.
- Burp Suite Intruder를 이용해 동일한 계정(admin)에 대해  
  여러 비밀번호를 반복 시도했지만 중간에 차단되거나 제한되는 동작이 없었습니다.
- CAPTCHA, 추가 인증(MFA), Rate Limiting 등 방어 기능도 존재하지 않아  
  자동화 도구를 이용한 Brute Force 공격이 가능한 환경입니다.

- 소스코드를 보면 로그인 실패 시 오류 메시지만 출력할 뿐, 실패 횟수를 카운트하거나 잠금 처리하는 로직이 전혀 없습니다.  
```php 
  if( $result && mysqli_num_rows( $result ) == 1 ) {
      echo "<p>Welcome to the password protected area {$user}</p>";
  } else {
      // 실패 횟수 카운트 없음 — 반복 시도 횟수에 제한이 없어 무제한 공격 가능
      echo "<pre><br />Username and/or password incorrect.</pre>";
  }
```

**판단 : 취약** — 실제 환경이었다면 자동화 도구를 통한 계정 탈취로 이어질 수 있습니다.

---

## 대응 방안

### 계정 잠금 정책 적용

- 로그인 실패 5~10회 초과 시 계정을 잠금 처리합니다.

```php
  session_start();
  
  if (!isset($_SESSION['login_attempts'])) {
      $_SESSION['login_attempts'] = 0;
  }
  
  $_SESSION['login_attempts']++;
  
  // 5회 이상 실패 시 추가 로그인 시도 차단
  if ($_SESSION['login_attempts'] >= 5) {
      die("로그인 시도 횟수를 초과했습니다. 잠시 후 다시 시도해주세요.");
  }
```

#### 핵심 효과

- 무차별 대입 공격 시도를 일정 횟수 이후 자동으로 차단합니다.

---

### Rate Limiting 적용

- 동일 IP 또는 계정에 대한 단위 시간 내 요청 횟수를 제한합니다.

```php
  $ip = $_SERVER['REMOTE_ADDR'];
  $cache_key = 'login_attempts_' . $ip;
  
  // 60초 내 10회 초과 시 요청 차단
  if (apcu_fetch($cache_key) > 10) {
      http_response_code(429);
      die("Too Many Requests. 잠시 후 다시 시도해주세요.");
  }
  
  // 요청 횟수 증가 (TTL 60초)
  apcu_inc($cache_key, 1, $success, 60);
```

#### 핵심 효과

- Burp Intruder 등 자동화 도구를 이용한 반복 요청을 차단합니다.

---

### CAPTCHA 적용

- 로그인 실패 일정 횟수 이후 CAPTCHA를 적용하여 자동화 도구의 반복 요청을 차단합니다.

#### 핵심 효과

- 봇을 이용한 자동화 공격을 차단합니다.

---

### 다중 인증(MFA) 적용

- ID/PW 외 OTP, SMS 인증, 이메일 인증 등 추가 인증 수단을 도입합니다.

#### 핵심 효과

- 비밀번호가 노출되더라도 계정 탈취를 방지할 수 있습니다.

---

### 비밀번호 정책 강화

- 최소 8자 이상, 영문 + 숫자 + 특수문자를 포함하도록 정책을 설정합니다.

#### 핵심 효과

- 단순한 비밀번호 사용을 방지하여 Brute Force 성공 확률을 낮춥니다.

---

### 로그인 실패 로그 및 탐지

- 로그인 실패 기록을 저장하고 동일 IP 반복 실패를 탐지하여 관리자에게 알림을 전송합니다.

#### 핵심 효과

- 공격 시도를 조기에 탐지하고 대응할 수 있습니다.
