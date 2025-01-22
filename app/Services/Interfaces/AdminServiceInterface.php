<?php

namespace App\Services\Interfaces;

interface AdminServiceInterface
{
    public function store($request);
    public function login($request);
    public function logout($request);
    public function manage($request);
    public function getInformation($request);
    public function update($request);
    public function delete($id);
}
