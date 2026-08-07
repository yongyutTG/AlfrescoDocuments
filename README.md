# UserAlfresco CI4

โปรเจคหน้าบ้าน CodeIgniter 4 สำหรับเรียก API จาก `UserAlfresco-api`

## Flow

```text
User login ที่ CI4
-> CI4 เรียก UserAlfresco-api /auth/login
-> UserAlfresco-api ตรวจ username/password กับ Alfresco
-> CI4 เก็บ accessToken ไว้ใน PHP session
-> หน้าเอกสารเรียก proxy API ของ CI4
-> CI4 แนบ Bearer token ไปเรียก UserAlfresco-api
```

## Run

ต้องเปิด `UserAlfresco-api` ก่อนที่ port 3001

```bash
cd C:\xampp\htdocs\UserAlfresco-api\backend
npm run dev
```

จากนั้นรัน CI4

```bash
cd C:\xampp\htdocs\UserAlfresco-ci4
php spark serve --port 8082
```

เปิดหน้าเว็บ:

```text
http://localhost:8082/login
```

## CI4 Routes

```text
GET  /login
POST /login
GET  /logout
GET  /documents
GET  /api/folders?path=/Sites/tg-saving/documentLibrary
GET  /api/documents?folderPath=/Sites/tg-saving/documentLibrary&q=026277&maxItems=100&skipCount=0
GET  /api/documents/{id}/content?name=file.pdf
```

## Node API ที่ถูกเรียกต่อ

```text
POST /auth/login
GET  /user-api/alfresco/folders
GET  /user-api/alfresco/documents
GET  /user-api/alfresco/documents/:id/content
```
# AlfrescoDocuments
