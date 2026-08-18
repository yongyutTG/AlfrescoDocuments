<?php

namespace App\Controllers;

use App\Libraries\UserAlfrescoApiClient;

class AuthController extends BaseController
{
    public function loginForm()
    {
        if (session()->has('alfresco_access_token')) {
            return redirect()->to('/documents');
        }

        return view('auth/login', [
            'error' => session()->getFlashdata('error'),
        ]);
    }

    public function login()
    {
        $username = trim((string) $this->request->getPost('username'));
        $password = (string) $this->request->getPost('password');

        if ($username === '' || $password === '') {
            return redirect()->back()->withInput()->with('error', 'กรุณากรอกชื่อผู้ใช้งานและรหัสผ่าน');
        }

        // Step 1: CI4 ส่ง username/password ไปให้ UserAlfresco-api ตรวจสอบกับ Alfresco
        $client = new UserAlfrescoApiClient();
        $result = $client->login($username, $password);

        if ($result['status'] < 200 || $result['status'] >= 300 || empty($result['data']['accessToken'])) {
            return redirect()->back()->withInput()->with('error', 'เข้าสู่ระบบไม่สำเร็จ กรุณาตรวจสอบชื่อผู้ใช้งานและรหัสผ่าน');
        }

        // Step 2: เก็บ accessToken ไว้ใน PHP session เพื่อใช้แนบ Bearer token ตอนเรียก API เส้นอื่น
        session()->set([
            'alfresco_access_token' => $result['data']['accessToken'],
            'alfresco_username'     => $result['data']['username'] ?? $username,
            'alfresco_expires_in'   => $result['data']['expiresInMs'] ?? null,
        ]);

        return redirect()->to('/documents');
    }

    public function logout()
    {
        session()->remove(['alfresco_access_token', 'alfresco_username', 'alfresco_expires_in']);

        return redirect()->to('/login');
    }
}
