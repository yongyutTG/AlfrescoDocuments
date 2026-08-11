# Function Flow: AlfrescoDocuments

เอกสารนี้อธิบาย function หลักที่ใช้ใน flow login, ค้นหาเอกสาร และเปิดไฟล์ของโปรเจค `AlfrescoDocuments`

## ภาพรวม Flow

```text
หน้า login
-> AuthController::login()
-> UserAlfrescoApiClient::login()
-> UserAlfresco-api /auth/login
-> ได้ accessToken
-> เก็บ token ใน PHP session

หน้า documents
-> documents.js loadDocuments()
-> CI4 /api/documents
-> DocumentController::documents()
-> UserAlfrescoApiClient::documents()
-> UserAlfresco-api /user-api/alfresco/documents
-> Alfresco CMIS
-> คืนรายการเอกสารกลับมาแสดงใน table

กดเปิดไฟล์
-> /api/documents/{id}/content
-> DocumentController::content()
-> UserAlfrescoApiClient::content()
-> UserAlfresco-api /user-api/alfresco/documents/{id}/content
-> stream ไฟล์กลับ browser
```

---

## 1. AuthController::login()

ไฟล์:

```text
app/Controllers/AuthController.php
```

หน้าที่:

```text
รับ username/password จากหน้า login แล้วส่งไปให้ UserAlfresco-api ตรวจสอบกับ Alfresco
```

Route ที่เรียก function นี้:

```http
POST /login
```

Input ที่รับจาก form:

| ชื่อ | มาจาก | ความหมาย |
|---|---|---|
| `username` | POST form | username ของ Alfresco |
| `password` | POST form | password ของ Alfresco |

Step การทำงาน:

```text
1. อ่าน username/password จาก request
2. trim username
3. ถ้า username หรือ password ว่าง ให้กลับหน้า login พร้อม error
4. สร้าง UserAlfrescoApiClient
5. เรียก $client->login($username, $password)
6. ตรวจ response จาก UserAlfresco-api
7. ถ้า status ไม่ใช่ 2xx หรือไม่มี accessToken ให้กลับหน้า login พร้อม error
8. ถ้าสำเร็จ เก็บ accessToken, username, expiresInMs ลง PHP session
9. redirect ไป /documents
```

Code สำคัญ:

```php
$result = $client->login($username, $password);
```

หมายถึง CI4 เรียกไปที่ Node API:

```http
POST /auth/login
```

เงื่อนไข login fail:

```php
if ($result['status'] < 200 || $result['status'] >= 300 || empty($result['data']['accessToken'])) {
    return redirect()->back()->withInput()->with('error', 'Login ไม่สำเร็จ กรุณาตรวจสอบ username/password');
}
```

ความหมาย:

```text
ถ้า HTTP status ไม่ใช่ 2xx หรือ response ไม่มี accessToken ถือว่า login ไม่สำเร็จ
```

ค่าที่เก็บใน session:

```php
session()->set([
    'alfresco_access_token' => $result['data']['accessToken'],
    'alfresco_username'     => $result['data']['username'] ?? $username,
    'alfresco_expires_in'   => $result['data']['expiresInMs'] ?? null,
]);
```

Output:

```text
สำเร็จ -> redirect ไป /documents
ไม่สำเร็จ -> redirect กลับหน้า login พร้อม error
```

---

## 2. DocumentController::documents()

ไฟล์:

```text
app/Controllers/DocumentController.php
```

หน้าที่:

```text
เป็น proxy API ของ CI4 สำหรับดึงหรือค้นหาเอกสารจาก UserAlfresco-api
```

Route ที่เรียก function นี้:

```http
GET /api/documents
```

Input ที่รับจาก query string:

| ชื่อ | ตัวอย่าง | ความหมาย |
|---|---|---|
| `folderPath` | `/Sites/tg-saving/documentLibrary` | folder ที่ต้องการค้น |
| `q` | `026277` | keyword สำหรับค้นชื่อไฟล์ |
| `maxItems` | `100` | จำนวนรายการต่อหน้า |
| `skipCount` | `0` | จำนวนรายการที่ข้าม ใช้ทำ pagination |

Step การทำงาน:

```text
1. ตรวจว่าผู้ใช้ login แล้วหรือยัง
2. ถ้ายังไม่ login คืน 401 Unauthorized
3. อ่าน folderPath, q, maxItems, skipCount จาก query string
4. จำกัด maxItems ให้อยู่ระหว่าง 1 ถึง 100
5. จำกัด skipCount ไม่ให้ต่ำกว่า 0
6. สร้าง UserAlfrescoApiClient
7. เรียก $client->documents() พร้อม token จาก session
8. คืน JSON จาก UserAlfresco-api กลับไปให้หน้าเว็บ
```

Code สำคัญ:

```php
$result = $client->documents($this->accessToken(), [
    'folderPath' => $folderPath,
    'q'          => $q,
    'maxItems'   => $maxItems,
    'skipCount'  => $skipCount,
]);
```

หมายถึง CI4 จะเรียก Node API:

```http
GET /user-api/alfresco/documents?folderPath=...&q=...&maxItems=...&skipCount=...
Authorization: Bearer ACCESS_TOKEN
```

Output:

```text
สำเร็จ -> JSON รายการเอกสาร
ไม่สำเร็จ -> JSON error หรือ raw body จาก UserAlfresco-api
```

หมายเหตุ:

```text
ตอนนี้ function นี้ส่ง q ไปยัง UserAlfresco-api
ถ้าต้องการใช้ exactName ที่เพิ่มใน UserAlfresco-api ต้องเพิ่ม query exactName ใน function นี้และหน้า documents.js ด้วย
```

---

## 3. DocumentController::content()

ไฟล์:

```text
app/Controllers/DocumentController.php
```

หน้าที่:

```text
เปิดหรือ stream ไฟล์จาก Alfresco กลับไปให้ browser
```

Route ที่เรียก function นี้:

```http
GET /api/documents/{id}/content?name=file.pdf
```

Input:

| ชื่อ | มาจาก | ความหมาย |
|---|---|---|
| `documentId` | path parameter | id ของเอกสารจาก Alfresco/CMIS |
| `name` | query string | ชื่อไฟล์ที่ใช้ตอนเปิดหรือดาวน์โหลด |

Step การทำงาน:

```text
1. ตรวจว่าผู้ใช้ login แล้วหรือยัง
2. ถ้ายังไม่ login redirect ไป /login
3. อ่าน name จาก query string
4. สร้าง UserAlfrescoApiClient
5. เรียก $client->content() พร้อม token จาก session
6. ถ้า UserAlfresco-api ตอบไม่สำเร็จ คืน error
7. ถ้าสำเร็จ ตั้ง header Content-Type เป็น application/pdf
8. ตั้ง Content-Disposition เป็น inline
9. ส่ง binary body กลับ browser
```

Code สำคัญ:

```php
$result = $client->content($this->accessToken(), rawurldecode($documentId), is_string($name) ? $name : null);
```

หมายถึง CI4 เรียก Node API:

```http
GET /user-api/alfresco/documents/{id}/content?name=file.pdf
Authorization: Bearer ACCESS_TOKEN
```

Header ที่ส่งกลับ browser:

```php
return $this->response
    ->setHeader('Content-Type', 'application/pdf')
    ->setHeader('Content-Disposition', 'inline; filename="' . addslashes($fileName) . '"')
    ->setBody($result['body']);
```

ความหมาย:

```text
ให้ browser เปิดไฟล์ใน tab ใหม่ ถ้าเป็น PDF
```

---

## 4. UserAlfrescoApiClient::documents()

ไฟล์:

```text
app/Libraries/UserAlfrescoApiClient.php
```

หน้าที่:

```text
เป็น function กลางของ CI4 สำหรับเรียก UserAlfresco-api เพื่อ list/search เอกสาร
```

ถูกเรียกจาก:

```text
DocumentController::documents()
```

Input:

| ชื่อ | Type | ความหมาย |
|---|---|---|
| `accessToken` | string | token ที่เก็บไว้ใน PHP session |
| `params` | array | query parameters เช่น folderPath, q, maxItems, skipCount |

Step การทำงาน:

```text
1. รับ accessToken และ params จาก DocumentController
2. สร้าง Authorization header แบบ Bearer
3. เรียก GET /user-api/alfresco/documents ไปยัง UserAlfresco-api
4. ส่ง params ไปเป็น query string
5. คืน response ที่ผ่าน jsonRequest()
```

Code:

```php
public function documents(string $accessToken, array $params): array
{
    return $this->jsonRequest('GET', '/user-api/alfresco/documents', [
        'headers' => $this->bearerHeaders($accessToken),
        'query'   => $params,
    ]);
}
```

ข้างในจะสร้าง header:

```http
Authorization: Bearer ACCESS_TOKEN
Accept: application/json
```

Output:

```php
[
    'status' => 200,
    'data'   => [...],
    'body'   => 'raw response body'
]
```

---

## 5. documents.js loadDocuments()

ไฟล์:

```text
public/assets/js/documents.js
```

หน้าที่:

```text
function หลักของหน้าเว็บตอนกดปุ่มค้นหา ใช้เรียก CI4 /api/documents แล้วนำผลลัพธ์มาแสดงใน table
```

ถูกเรียกจาก:

```js
searchForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    state.page = 1;
    await loadDocuments();
});
```

และถูกเรียกจากปุ่ม pagination:

```text
หน้าแรก
«
»
```

Input ที่อ่านจากหน้าเว็บ:

| ตัวแปร | มาจาก element | ความหมาย |
|---|---|---|
| `state.folderPath` | folder ที่เลือก | path ของ folder |
| `keywordInput.value` | ช่องค้นหา | keyword q |
| `pageSizeInput.value` | select จำนวนต่อหน้า | maxItems |
| `state.page` | state ใน JS | หน้าปัจจุบัน |

Step การทำงาน:

```text
1. อ่าน maxItems จาก dropdown
2. คำนวณ skipCount จาก page ปัจจุบัน
3. แสดงข้อความ "กำลังดึงเอกสาร..."
4. ล้าง table เดิม
5. disable pagination ชั่วคราว
6. สร้าง URL /api/documents
7. ใส่ query string: folderPath, q, maxItems, skipCount
8. เรียก requestJson(url)
9. แปลง response ให้เป็น array ด้วย pickItems()
10. กรองเอาเฉพาะ document
11. เก็บจำนวนรายการและ hasMoreItems ลง state
12. เรียก renderRows(items)
13. อัปเดตจำนวนรายการ
14. อัปเดต pagination
15. แสดงข้อความพบเอกสารหรือไม่พบเอกสาร
```

Code สำคัญ:

```js
const url = new URL(config.endpoints.documents, window.location.origin);
url.searchParams.set('folderPath', state.folderPath);
url.searchParams.set('q', keywordInput.value.trim());
url.searchParams.set('maxItems', String(maxItems));
url.searchParams.set('skipCount', String(skipCount));
```

หมายถึง browser เรียก CI4:

```http
GET /api/documents?folderPath=...&q=...&maxItems=100&skipCount=0
```

ไม่เรียก UserAlfresco-api ตรง ๆ

จากนั้น:

```js
const payload = await requestJson(url);
const items = pickItems(payload).filter((item) => item.isDocument !== false && item.type !== 'cmis:folder');
```

คือแปลง response เป็นรายการเอกสาร

แล้ว:

```js
renderRows(items);
```

คือเอารายการเอกสารไปแสดงใน table

Output:

```text
แสดงรายการเอกสารใน table
อัปเดตจำนวนรายการ
อัปเดตปุ่ม pagination
แสดงสถานะพบ/ไม่พบเอกสาร
```

---

## สรุปการเรียก function ต่อกัน

### Login

```text
POST /login
-> AuthController::login()
-> UserAlfrescoApiClient::login()
-> UserAlfresco-api POST /auth/login
-> session()->set('alfresco_access_token')
-> redirect /documents
```

### Search/List Documents

```text
กดปุ่มค้นหา
-> documents.js loadDocuments()
-> GET /api/documents
-> DocumentController::documents()
-> UserAlfrescoApiClient::documents()
-> UserAlfresco-api GET /user-api/alfresco/documents
-> Alfresco CMIS Query
-> renderRows()
```

### Open File

```text
กดปุ่มเปิดไฟล์
-> GET /api/documents/{id}/content
-> DocumentController::content()
-> UserAlfrescoApiClient::content()
-> UserAlfresco-api GET /user-api/alfresco/documents/{id}/content
-> Alfresco CMIS content stream
-> browser เปิด PDF
```

---

## จุดที่ควรรู้

```text
Browser ไม่ถือ Bearer token ของ UserAlfresco-api โดยตรง
CI4 เก็บ token ไว้ใน PHP session
Browser เรียกแค่ /api/... ของ CI4
CI4 เป็นคนแนบ Bearer token ไปหา UserAlfresco-api
```

ถ้าจะเพิ่มค้นชื่อไฟล์ตรงตัว:

```text
UserAlfresco-api รองรับ exactName แล้ว
แต่ AlfrescoDocuments ตอนนี้ยังส่ง q จากหน้าเว็บ
ถ้าต้องการใช้ exactName ต้องเพิ่มช่อง/logic ใน documents.js และ DocumentController::documents()
```
