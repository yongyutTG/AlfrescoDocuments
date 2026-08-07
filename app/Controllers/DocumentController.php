<?php

namespace App\Controllers;

use App\Libraries\UserAlfrescoApiClient;

class DocumentController extends BaseController
{
    private string $rootPath = '/Sites/tg-saving/documentLibrary';

    public function index()
    {
        if (! $this->isLoggedIn()) {
            return redirect()->to('/login');
        }

        return view('documents/index', [
            'username' => session('alfresco_username'),
            'rootPath' => $this->rootPath,
        ]);
    }

    public function folders()
    {
        if (! $this->isLoggedIn()) {
            return $this->response->setStatusCode(401)->setJSON(['message' => 'Unauthorized']);
        }

        $path = (string) ($this->request->getGet('path') ?: $this->rootPath);

        // Step 1: หน้าเว็บขอรายการ folder ผ่าน CI4
        // Step 2: CI4 แนบ token จาก session ไปเรียก UserAlfresco-api /user-api/alfresco/folders
        $client = new UserAlfrescoApiClient();
        $result = $client->folders($this->accessToken(), $path);

        return $this->response->setStatusCode($result['status'])->setJSON($result['data'] ?? [
            'message' => 'Cannot read folders',
            'raw'     => $result['body'],
        ]);
    }

    public function documents()
    {
        if (! $this->isLoggedIn()) {
            return $this->response->setStatusCode(401)->setJSON(['message' => 'Unauthorized']);
        }

        $folderPath = (string) ($this->request->getGet('folderPath') ?: $this->rootPath);
        $q = trim((string) $this->request->getGet('q'));
        $maxItems = (int) ($this->request->getGet('maxItems') ?: 100);
        $skipCount = (int) ($this->request->getGet('skipCount') ?: 0);

        $maxItems = max(1, min($maxItems, 100));
        $skipCount = max(0, $skipCount);

        // Step 1: หน้าเว็บกดปุ่มค้นหา
        // Step 2: CI4 ส่ง folderPath/q/maxItems/skipCount ไปให้ UserAlfresco-api
        // Step 3: UserAlfresco-api ไปค้นที่ Alfresco ด้วยสิทธิ์ของ user ที่ login
        $client = new UserAlfrescoApiClient();
        $result = $client->documents($this->accessToken(), [
            'folderPath' => $folderPath,
            'q'          => $q,
            'maxItems'   => $maxItems,
            'skipCount'  => $skipCount,
        ]);

        return $this->response->setStatusCode($result['status'])->setJSON($result['data'] ?? [
            'message' => 'Cannot read documents',
            'raw'     => $result['body'],
        ]);
    }

    public function content(string $documentId)
    {
        if (! $this->isLoggedIn()) {
            return redirect()->to('/login');
        }

        $name = $this->request->getGet('name');

        // Step 1: user กดเปิดไฟล์
        // Step 2: CI4 แนบ token จาก session แล้วเรียก UserAlfresco-api /documents/:id/content
        // Step 3: ส่ง binary content กลับ browser เพื่อเปิด PDF/ดาวน์โหลด
        $client = new UserAlfrescoApiClient();
        $result = $client->content($this->accessToken(), rawurldecode($documentId), is_string($name) ? $name : null);

        if ($result['status'] < 200 || $result['status'] >= 300) {
            return $this->response->setStatusCode($result['status'])->setBody('เปิดไฟล์ไม่สำเร็จ');
        }

        $fileName = is_string($name) && $name !== '' ? $name : 'document.pdf';

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . addslashes($fileName) . '"')
            ->setBody($result['body']);
    }

    private function isLoggedIn(): bool
    {
        return (bool) session('alfresco_access_token');
    }

    private function accessToken(): string
    {
        return (string) session('alfresco_access_token');
    }
}
