<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index()
    {
        return redirect()->to(session('alfresco_access_token') ? '/documents' : '/login');
    }
}
