<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Admin;
use App\Services\Interfaces\AdminServiceInterface as AdminService;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    protected $adminService;

    public function __construct(AdminService $adminService)
    {
        $this->adminService = $adminService;
    }

    public function store(Request $request)
    {
        try {
            // $now = now()->timestamp;
            // DB::table('adminlogs')->insert([
            //     'admin_id' => Auth::guard('admin')->user()->id,
            //     'time' => $now,
            //     'ip' => $request->ip() ?? null,
            //     'action' => 'add a admin',
            //     'cat' => 'admin',
            // ]);
            $store = $this->adminService->store($request);
            return $store;
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function login(Request $request)
    {
        try {
            $login = $this->adminService->login($request);
            return $login;
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function logout(Request $request)
    {
        try {
            $logout = $this->adminService->logout($request);
            return $logout;
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function getInformation(Request $request)
    {
        try {
            // $now = date('d-m-Y H:i:s');
            // $stringTime = strtotime($now);
            // DB::table('adminlogs')->insert([
            //     'admin_id' => Auth::guard('admin')->user()->id,
            //     'time' =>  $stringTime,
            //     'ip' => $request ? $request->ip() : null,
            //     'action' => 'get Information a admin',
            //     'cat' => 'admin',
            // ]);
            $getInformation = $this->adminService->getInformation($request);
            return $getInformation;
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function update(Request $request)
    {
        try {
            // $now = date('d-m-Y H:i:s');
            // $stringTime = strtotime($now);
            // DB::table('adminlogs')->insert([
            //     'admin_id' => Auth::guard('admin')->user()->id,
            //     'time' =>  $stringTime,
            //     'ip' => $request ? $request->ip() : null,
            //     'action' => 'update a admin',
            //     'cat' => 'admin',
            // ]);
            $update = $this->adminService->update($request);
            return $update;
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }
    
    public function manage(Request $request)
    {
        try {
            $manage = $this->adminService->manage($request);
            return $manage;
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }
    public function delete(Request $request, $id)
    {
        try {
            $id = (int) $id;
            $now = date('d-m-Y H:i:s');
            $stringTime = strtotime($now);
            DB::table('adminlogs')->insert([
                'admin_id' => Auth::guard('admin')->user()->id,
                'time' =>  $stringTime,
                'ip' => $request ? $request->ip() : null,
                'action' => 'delete a admin',
                'cat' => 'admin',
            ]);
            $destroy = $this->adminService->delete($id);
            return $destroy;
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }
}
