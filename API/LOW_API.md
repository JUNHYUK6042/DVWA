# API 취약점

## 개요

- 본 실습에서는 API 취약점을 대상으로, 동일 서비스에서 운영 중인  
  구버전 API(v1)와 신버전 API(v2) 간의 보안 설계 차이를 점검했습니다.
- v1 API에서 인가되지 않은 사용자 ID 열거(BOLA)와 패스워드 해시 과도 노출이 가능한지 확인하여 취약점 존재 여부를 점검했습니다.
- 주요정보통신기반시설 기술적 취약점 분석·평가 방법 상세가이드의 웹(Web Application) 항목 중 불충분한 권한 검증(IN)과 연계됩니다.

---

## 주요정보통신기반시설 기준

> 출처 : https://www.kisa.or.kr/2060204/form?postSeq=22&page=1#fnPostAttachDownload ( p.733 ~ 734 )

### 점검 내용

- 타 사용자의 권한을 탈취하여 민감한 데이터 접근 및 수정 가능 여부를 점검합니다.

---

### 점검 목적

- 사용자 검증 로직을 서버 사이드에서 구현하여 비인가자로부터 악의적인 접근을 차단합니다.

---

### 보안 위협

- 패킷 변조, 클라이언트 측 로직 변조 등을 통해 사용자 식별 가능한 시퀀스 데이터를 변조하여  
  타 사용자의 권한을 탈취할 경우 개인정보 유출 및 데이터 조작이 가능합니다.

---

### 판단 기준

#### 양호

- 중요 페이지에 사용자 검증 로직이 구현되어 있어 타 사용자의 권한 탈취가 제한된 경우

#### 취약

- 중요 페이지에 사용자 검증 로직이 미흡하여 타 사용자의 권한 탈취가 가능한 경우

---

## 점검 절차

1. DVWA API Security 페이지 접속 및 구조 확인
2. 브라우저 개발자 도구 Network 탭에서 API 엔드포인트 패턴 확인
3. v2 엔드포인트를 기반으로 구버전 v1 API 접근 시도
4. v1 API에서 사용자 ID 순차 열거 (id: 1, 2, 3)
5. 획득한 패스워드 해시를 CrackStation으로 크랙

---

## 취약점 검증 및 공격 수행

### API Security 초기 화면

- DVWA의 API Security 페이지로, 테이블 생성 시 사용한 API 호출을 찾아 추가 정보를 반환할 수 있는지 확인하는 실습 환경입니다.

![01](/API/img/01.png)

---

### Network 탭에서 API 엔드포인트 발견

![02](/API/img/02.png)

- 브라우저 개발자 도구(F12) → Network 탭을 열어 페이지 로드 시 발생하는 요청 목록을 확인했습니다.
- 목록 중 `/DVWA/vulnerabilities/api/v2/user/` 엔드포인트가 호출되고 있음을 확인했습니다.

![03](/API/img/03.png)

- `/DVWA/vulnerabilities/api/v2/user/` 요청이 404 Not Found로 응답하는 것을 확인했습니다.
- v2 엔드포인트 패턴(`/api/v2/user/`)을 파악했으므로  
  구버전 `/api/v1/user/`도 존재할 수 있다고 추론하여 직접 접근을 시도했습니다.

---

### v1 API 접근 및 패스워드 해시 노출 확인

- 다음과 같은 URL을 입력합니다.  
```
 http://192.168.11.36/DVWA/vulnerabilities/api/v1/user/1
```

![04](/API/img/04.png)

- URL을 `/api/v1/user/1`로 직접 수정하여 접근했습니다.
- v1 API는 `id`, `name`, `level` 외에 `password` 해시까지 응답에 포함하고 있었습니다.

---

### v1과 v2 응답 비교

- 다음과 같은 URL을 입력합니다.  
```
 http://192.168.11.36/DVWA/vulnerabilities/api/v2/user/1
```

![05](/API/img/05.png)

- 확인한 결과 동일한 사용자(tony, id:1)에 대해 v2 API로 접근하자 `password` 필드가 응답에 포함되지 않았습니다.
- v1 API에서만 민감 정보가 과도하게 노출되는 구조임을 확인했습니다.

---

### 사용자 ID 열거— id:2 (morph)

- ID를 2로 변경하여 다음과 같은 URL을 입력합니다.  
```
 http://192.168.11.36/DVWA/vulnerabilities/api/v1/user/2
```

![06](/API/img/06.png)

- `/v1/user/2`에 접근하자 사용자 morph의 정보와 패스워드 해시가 반환되었습니다.

---

### 사용자 ID 열거 — id:3 (chas)

- ID를 3로 변경하여 다음과 같은 URL을 입력합니다.  
```
 http://192.168.11.36/DVWA/vulnerabilities/api/v1/user/3
```

![07](/API/img/07.png)

- `/v1/user/3`에 접근하자 사용자 chas의 정보와 패스워드 해시가 반환되었습니다.
- `/v1/user/4`는 404로 응답하여 총 사용자가 3명임을 확인했습니다.

---

### 획득한 해시 크랙

- 수집한 3개의 SHA-256 해시를 CrackStation에 입력하여 크랙을 시도했습니다.

![08](/API/img/08.png)

| 사용자 | 해시 (SHA-256) | 크랙 결과 |
|--------|----------------|-----------|
| tony (id:1) | 1c8bfe8f...b63032 | letmein |
| morph (id:2) | e5326ba4...f90a8 | TonyHart |
| chas (id:3) | a09237fc...c21de | Hartbeat |

- 3개의 해시가 모두 크랙되어 각 사용자의 실제 패스워드가 확인되었습니다.
- SHA-256은 연산 속도가 빠르기 때문에 레인보우 테이블 기반 크랙 도구에 취약합니다.

---

## 취약점 분석

- 소스코드의 JavaScript를 보면 페이지가 로드될 때  
  `get_users()` 함수가 실행되며, API 엔드포인트 URL이 소스코드에 그대로 노출됩니다.

```javascript
  function get_users() {
      const url = '" . $stripped_url . "/vulnerabilities/api/v2/user/';
      // API 엔드포인트 경로가 클라이언트 소스코드에 노출 — 공격자가 URL 패턴을 파악 가능
```

- `loadTableData` 함수는 API 응답에 `password` 키가 포함되면 성공 메시지를 출력합니다.

```javascript
  Object.keys(item).forEach(function(k){
      if (k == 'password') {
          successDiv = document.getElementById('message');
          successDiv.style.display = 'block';
          // 응답에 password 필드가 존재할 경우를 가정하고 처리 — v1 API에서 password가 반환됨을 암시
      }
  });
```

- v2 엔드포인트 패턴(`/api/v2/user/`)이 소스코드에서 노출되므로, 공격자는 v1으로 버전을 낮춰 접근을 시도할 수 있습니다.
- v1 API는 인증 없이 임의 ID를 조회할 수 있고, 응답에 `password` 해시를 포함합니다.
- 페이지에 등록된 성공 메시지 `'Well done, you found the password hashes.'`도 password 노출이 의도된 공격 경로임을 보여줍니다.

```php
  $request_url = $_SERVER['REQUEST_URI'];
  $stripped_url = str_replace("/vulnerabilities/api/", "", $request_url);
  // 현재 URL에서 경로를 가공해 API 베이스 URL을 생성 — 경로 패턴이 소스코드에 그대로 삽입됨
```

**판단 : 취약** — 소스코드에 노출된 엔드포인트 패턴과 구버전 API 미폐기, 과도한 데이터 노출이 결합되어 전체 사용자의 패스워드 해시가 수집되고 크랙까지 이어집니다.

---

## 대응 방안

### 구버전 API 폐기 또는 접근 차단

- 사용하지 않는 구버전 API 엔드포인트는 완전히 제거하거나 라우팅 수준에서 차단합니다.

```apache
  RewriteEngine On
  RewriteRule ^v1/.*$ - [F,L]
  # v1 하위 모든 경로 요청에 403 Forbidden 반환 — 구버전 엔드포인트 자체를 차단
```

#### 핵심 효과

- 구버전 API로의 접근 자체를 막아 버전 다운그레이드 공격이 불가능합니다.

---

### 객체 수준 접근 제어 적용 (BOLA 방지)

- API 요청 시 로그인된 사용자 본인의 데이터만 조회할 수 있도록 권한 검증을 추가합니다.

```php
  $requestedId = $_GET['id'];
  $sessionUserId = $_SESSION['user_id'];
  // 세션에서 현재 로그인한 사용자 ID를 가져옴
  
  if ($requestedId != $sessionUserId) {
      http_response_code(403);
      // 요청 ID와 세션 사용자 ID가 다르면 403 반환 — 타 사용자 데이터 조회 차단
      echo json_encode(['error' => 'Access denied']);
      exit;
  }
```

#### 핵심 효과

- ID를 1, 2, 3으로 바꿔도 자신의 데이터 외에는 조회할 수 없습니다.

---

### 응답 데이터 최소화 (Excessive Data Exposure 방지)

- API 응답에서 클라이언트에 불필요한 민감 정보를 제거합니다.

```php
  $response = [
      'id'    => $row['id'],
      'name'  => $row['name'],
      'level' => $row['level']
      // password 필드 제거 — 클라이언트가 패스워드 해시를 받을 이유가 없음
  ];
  echo json_encode($response);
```

#### 핵심 효과

- 해시가 노출되지 않아 크랙 공격으로 이어지지 않습니다.

---

### 패스워드 해시 알고리즘 강화

- SHA-256은 연산 속도가 빨라 크랙에 취약합니다. bcrypt 등 느린 해시 알고리즘을 적용합니다.

```php
  $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT, ['cost' => 12]);
  // bcrypt cost 12 적용 — SHA-256 대비 연산 속도를 극단적으로 낮춰 무차별 대입 속도 저하
  
  if (password_verify($input, $hashedPassword)) {
      // password_verify()로 검증 — 타이밍 공격 방지 및 해시 비교로 원문 노출 차단
      $success = true;
  }
```

#### 핵심 효과

- 해시가 유출되더라도 크랙에 필요한 시간이 수십~수백 배 이상 증가합니다.
