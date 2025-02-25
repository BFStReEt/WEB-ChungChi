<?php

namespace App\Services\Interfaces;

/**
 * Interface AdminServiceInterface
 * @package App\Services\Interfaces
 */
interface AdminServiceInterface
{
    public function login($request);
    public function information();
    public function logout();
    public function index($request);
    public function store($request);
    public function edit($id);
    public function update($request, $id);
    public function destroy($id);
}
