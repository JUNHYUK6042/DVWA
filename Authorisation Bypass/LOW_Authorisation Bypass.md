# Authorisation Bypass 취약점 실습

## 개요
- 본 실습에서는 DVWA의 Low 단계에서 Authorisation Bypass 취약점을 대상으로  
  서버사이드 권한 검증 로직의 부재로 인해 일반 사용자가 관리자 전용 페이지에 접근 가능한지 확인하여  
  인가 우회 취약점 존재 여부를 점검했습니다.

- 주요정보통신기반시설 기술적 취약점 분석·평가 방법 상세가이드에 Authorisation Bypass 독립 항목은 없으며,  
  프로세스 검증 누락(p.739) 항목과 연계하여 점검하였습니다.

---

## 주요정보통신기반시설 기준

> 출처 : https://www.kisa.or.kr/2060204/form?postSeq=22&page=1#fnPostAttachDownload ( p.739~743 )

### 점검 내용 (13. 프로세스 검증 누락 / p.739)
- 서비스 제공에 필요한 사용자 입력 및 실행단계의 흐름에 대한 검증의 적절성 여부를 점검합니다.

---

### 점검 목적
- 서버 사이드에서 적절한 검증을 통해 비정상적인 입력 및 프로세스 흐름으로 허용되지 않은 기능에 접근하는 것을 차단하기 위함입니다.

---

### 보안 위협
- 프로세스 또는 기능에 대한 접근 제어 및 검증이 미흡할 경우, URL 직접 접근 등 비정상적인 논리 오류를 유발하여  
  중요 페이지에 대한 무단 접근 및 개인정보 유출이 가능합니다.

---

### 판단 기준

#### 양호
- 중요 페이지에 사용자 검증 로직이 구현되어 있어, 타 사용자의 권한 탈취 및 URL 직접 접근이 제한된 경우

#### 취약
- 중요 페이지에 사용자 검증 로직이 미흡하여, 타 사용자의 권한 탈취 및 URL 직접 접근이 가능한 경우

---

## 점검 절차

1. admin 계정으로 Authorisation Bypass 페이지 정상 접근 확인
2. 로그아웃 후 gordonb 계정으로 로그인
3. gordonb 계정으로 관리자 전용 URL 직접 접근 시도
4. Burp Suite HTTP history를 통해 권한 검증 부재 확인

---

## 취약점 검증 및 공격 수행

### 실습 화면 (admin 정상 접근)

- DVWA의 Authorisation Bypass 페이지로, admin 계정으로  
  로그인한 상태에서는 정상적으로 사용자 관리 페이지에 접근할 수 있습니다.
- 페이지 상단에 "This page should only be accessible by the admin user."라는  
  문구가 명시되어 있어, 해당 페이지는 admin 전용 기능임을 확인할 수 있습니다.

<img src="/Authorisation%20Bypass/img/01.png" width="100%">

---

### gordonb 계정 로그인

- admin 계정에서 로그아웃 후, 일반 사용자 계정인 gordonb / abc123으로 로그인했습니다.

<img src="/Authorisation%20Bypass/img/02.png" width="100%">

---

### gordonb 로그인 후 메인 화면 확인

- gordonb 계정으로 로그인한 후 DVWA 메인 화면을 확인했습니다.
- 좌측 메뉴에 Authorisation Bypass 항목이 존재하지 않아,  
  클라이언트 측에서는 해당 기능에 접근할 수 없는 것처럼 보입니다.
- 그러나 이는 UI에서 숨긴 것일 뿐이며, 서버사이드 권한 검증이 없어 URL 직접 입력으로 우회가 가능합니다.

<img src="/Authorisation%20Bypass/img/03.png" width="100%">

---

### URL 직접 접근을 통한 인가 우회 확인

- gordonb 계정으로 로그인한 상태에서 아래 URL을 직접 입력하여 접근을 시도했습니다.

```
http://192.168.11.36/DVWA/vulnerabilities/authbypass/
```

- 서버가 세션의 권한을 검증하지 않아 admin 전용 사용자 관리 페이지가 그대로 노출되었습니다.
- 페이지에는 Bob, Pablo, Hack, Gordon, admin 전체 사용자 정보와 Update 기능까지 표시되는 것을 확인할 수 있습니다.

<img src="/Authorisation%20Bypass/img/04.png" width="100%">

---

### Burp Suite HTTP history 확인

- Burp Suite HTTP history에서 gordonb 세션으로 `get_user_data.php`에 요청이 전송된 것을 확인할 수 있습니다.

**Request 탭**
- `GET /DVWA/vulnerabilities/authbypass/get_user_data.php`
- `Cookie: PHPSESSID=e6cd2062c8c69600284a4e60ef5e90af; security=low` — gordonb 세션

**Response 탭**
- `HTTP/1.1 200 OK` — 권한 검증 없이 정상 응답
- 전체 사용자 데이터(admin, Gordon, Hack, Pablo, Bob)가 그대로 반환됨

<img src="/Authorisation%20Bypass/img/05.png" width="100%">

---

### 소스코드 확인
- View Source를 통해 PHP 소스코드를 확인한 결과, 권한 검증 로직이 존재하지 않습니다.

```php
<?php
/*
Nothing to see here for this vulnerability, have a look
instead at the dvwaHtmlEcho function in:
* dvwa/includes/dvwaPage.inc.php
*/
// Low 레벨은 권한 검증 로직이 존재하지 않아 누구든 페이지 접근 가능
?>
```

- low.php 파일 자체에 권한 검증 코드가 전혀 존재하지 않으며,  
  페이지 렌더링 함수에도 역할(role) 기반 접근 제어가 적용되어 있지 않습니다.
- 서버는 요청자의 세션 권한을 확인하지 않고 페이지를 그대로 반환하는 구조입니다.

---

## 취약점 분석

- gordonb 일반 사용자 계정으로 관리자 전용 URL에 직접 접근하여 페이지가 정상 노출되는 것을 확인했습니다.
- 서버사이드에서 세션 권한 검증을 수행하지 않아 모든 인증된 사용자가 관리자 기능에 접근 가능한 상태입니다.
- admin, Gordon, Hack, Pablo, Bob 전체 사용자 정보 및 수정 기능이 일반 사용자에게 노출되었습니다.
- **판단 : 취약** — 실제 환경이었다면 개인정보 유출, 계정 정보 무단 수정, 권한 상승 공격으로 이어질 수 있는 위험한 상태입니다.

---

## 대응 방안

### 서버사이드 세션 기반 권한 검증 구현

- 모든 보호 페이지에 요청자의 세션 권한을 검증하는 로직을 서버사이드에서 구현합니다.

```php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    header('Location: /DVWA/index.php');
    exit;
}
```

#### 핵심 효과
- URL을 직접 입력하더라도 서버가 세션 권한을 검증하여 비인가 접근을 차단할 수 있습니다.

---

### RBAC(역할 기반 접근 제어) 적용

- 사용자 역할(role)에 따라 접근 가능한 기능을 분리하고, 각 기능에 대한 권한 매트릭스를 정의하여 서버사이드에서 일관되게 적용합니다.

#### 핵심 효과
- 권한 없는 사용자의 모든 관리자 기능 접근을 체계적으로 차단할 수 있습니다.

---

### 클라이언트 사이드 접근 제어 의존 금지

- UI에서 메뉴 항목을 숨기거나 버튼을 비활성화하는 방식은 서버사이드 검증을 대체할 수 없습니다.
- 반드시 서버사이드에서 권한 검증을 수행해야 합니다.

#### 핵심 효과
- URL 직접 접근, Burp Suite 패킷 변조 등 클라이언트 우회 시도를 원천 차단할 수 있습니다.

---

### 권한 없는 접근 시도 로깅 및 알림 설정

- 비인가 접근 시도에 대한 로그를 기록하고 관리자에게 알림을 발송하여 이상 행위를 탐지합니다.

#### 핵심 효과
- 공격 시도를 조기에 탐지하고 대응할 수 있습니다.
