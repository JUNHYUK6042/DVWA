# DVWA
DVWA(Damn Vulnerable Web Application) 환경에서 주요 웹 취약점을 직접 점검·분석하고,  
주요정보통신기반시설 기술적 취약점 분석·평가 기준에 따라 보고서로 정리한 문서입니다.

---

## 실습 환경

| 구분 | 내용 |
|------|------|
| OS | Kali Linux |
| 대상 | DVWA (Damn Vulnerable Web Application) |
| 도구 | Burp Suite |
| 기준 | 주요정보통신기반시설 기술적 취약점 분석·평가 기준 |

---

## 📂 실습 목차

### 🔐 인증 / 세션

| 실습 | 설명 |
|------|------|
| [Brute Force](./Brute%20Force/LOW_Brute%20Force.md) | 무차별 대입을 통한 계정 탈취 점검 |
| [CSRF](./CSRF/LOW_CSRF.md) | 인증 세션을 악용한 요청 위조 점검 |
| [Authorisation Bypass](./Authorisation%20Bypass/LOW_Authorisation%20Bypass.md) | 권한 검증 미흡으로 인한 비인가 접근 점검 |
| [Weak Session IDs](./Weak_Session_IDs/LOW_Weak_Session_IDs.md) | 예측 가능한 세션 ID로 인한 세션 탈취 점검 |
| [Insecure CAPTCHA](./Insecure%20Captcha/LOW_Insecure%20Captcha.md) | CAPTCHA 검증 누락으로 인한 프로세스 우회 점검 |

### 💉 Injection

| 실습 | 설명 |
|------|------|
| [Command Injection](./Command%20Injection/LOW_Command%20Injection.md) | 시스템 명령어 주입 점검 |
| [SQL Injection](./SQL%20Injection/LOW_SQL%20Injection.md) | DB 조작을 위한 SQL 구문 주입 점검 |
| [Blind SQL Injection](./Blind%20SQL%20Injection/LOW_Blind%20SQL%20Injection.md) | 응답 차이를 이용한 SQLi 심화 점검 |

### 📁 파일

| 실습 | 설명 |
|------|------|
| [File Inclusion](./File%20Inclusion/LOW_File%20Inclusion.md) | 내부·외부 파일 포함(LFI/RFI) 점검 |
| [File Upload](./File%20Upload/LOW_File_Upload.md) | 악성 파일 업로드 점검 |

### 🖥️ XSS / 클라이언트

| 실습 | 설명 |
|------|------|
| [XSS (Reflected)](./XSS%20(Reflected)/LOW_XSS%20(Reflected).md) | 반사형 스크립트 삽입 점검 |
| [XSS (Stored)](./XSS%20(Stored)/LOW_XSS%20(Stored).md) | 저장형 스크립트 삽입 점검 |
| [XSS (DOM)](./XSS%20(DOM)/LOW_XSS%20(DOM).md) | DOM 기반 스크립트 삽입 점검 |
| [CSP Bypass](./CSP%20Bypass/LOW_CSP_Bypass.md) | 콘텐츠 보안 정책(CSP) 우회 점검 |
| [JavaScript Attacks](./JavaScript%20Attacks/LOW_JavaScript%20Attacks.md) | 클라이언트 JavaScript 보안 로직 노출 점검 |

### 🔀 리다이렉트

| 실습 | 설명 |
|------|------|
| [Open HTTP Redirect](./Open%20HTTP%20Redirect/LOW_Open%20HTTP%20Redirect.md) | 검증되지 않은 외부 URL 리다이렉트 점검 |
