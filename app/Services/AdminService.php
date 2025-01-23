<?php

namespace App\Services;

use App\Services\Interfaces\AdminServiceInterface;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Policies\AdminPolicy;
use Laravel\Passport\TokenRepository;
use Laravel\Passport\RefreshTokenRepository;
use Illuminate\Support\Facades\DB;
use Gate;

class AdminService implements AdminServiceInterface
{
    public function create($request)
    {
        abort_if(!$this->permissionPolicy->hasPermission($this->user, 'THÔNG TIN QUẢN TRỊ.Quản lý tài khoản admin.add'), 403, "No permission");

        $validator = Validator::make($request->all(), [
            'username' => 'required',
            'password' => 'required',
            'email' => 'nullable|email',
            'display_name' => 'nullable|string',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'phone' => 'nullable|string',
            'status' => 'nullable|integer',
            'role_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'false',
                'errors' => $validator->errors()
            ], 422);
        }

        $check = Admin::where('username', $request->username)->first();
        if ($check) {
            return response()->json([
                'message' => 'Username exits',
                'status' => false
            ], 409);
        }

        $userAdmin = new Admin();
        $userAdmin->username = $request->username;
        $userAdmin->password = Hash::make($request->password);
        $userAdmin->email = $request->email;
        $userAdmin->display_name = !empty($request->display_name) ? $request->display_name : 'Khách';
        $filePath = '';
        if (!empty($request->avatar) && is_array($request->avatar)) {
            $avatarFile = $request->avatar[0] ?? null;
        
            if ($avatarFile instanceof \Illuminate\Http\UploadedFile) {
                $uploadDir = public_path('uploads' . DIRECTORY_SEPARATOR . 'admin');
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
        
                $fileName = uniqid() . '.' . $avatarFile->getClientOriginalExtension();
                $filePath = 'uploads/admin/' . $fileName;
                $avatarFile->move($uploadDir, $fileName);
        
                $userAdmin->avatar = $filePath;
            } elseif (is_string($avatarFile) && str_contains($avatarFile, ';base64,')) {
                $file_chunks = explode(';base64,', $avatarFile);
                $fileType = explode('image/', $file_chunks[0] ?? '');
        
                if (isset($file_chunks[1], $fileType[1])) {
                    $base64Img = base64_decode($file_chunks[1]);
                    $imageType = $fileType[1];
        
                    $uploadDir = public_path('uploads' . DIRECTORY_SEPARATOR . 'admin');
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
        
                    $fileName = uniqid() . '.' . $imageType;
                    $filePath = 'uploads/admin/' . $fileName;
                    file_put_contents($uploadDir . DIRECTORY_SEPARATOR . $fileName, $base64Img);
        
                    $userAdmin->avatar = $filePath;
                }
            }
        }
        $userAdmin->avatar = $filePath;

        $userAdmin->skin = '';
        $userAdmin->is_default = 0;
        $userAdmin->lastlogin = NULL;
        $userAdmin->code_reset = Hash::make($request->password);
        $userAdmin->menu_order = 0;
        $userAdmin->phone = $request->phone;
        $userAdmin->status = $request->status;
        $userAdmin->depart_id = $request->depart_id;

        $userAdmin->save();

        if ($request->has('role_id') && is_array($request->role_id)) {
            $userAdmin->roles()->attach($request->role_id);
        }

        return response()->json([
            'status' => true,
            'userAdmin' => $userAdmin,
        ]);
    }

    public function login($request)
    {
        $val = Validator::make($request->all(), [
            'username' => 'required',
            'password' => 'required',
            //'recaptcha-response' => 'required',
        ],); 
        
        if ($val->fails()) {
            $errorMessage = $val->errors()->first();
        
            return response()->json([
                'status' => false,
                'message' => $errorMessage,
            ], 202);
        }

        //reCAPTCHA
        // $client = new \GuzzleHttp\Client();
        // $response = $client->post('https://www.google.com/recaptcha/api/siteverify', [
        //     'form_params' => [
        //         'secret' => config('services.recaptcha.secret_key'),
        //         'response' => $request->input('recaptcha-response'),
        //         'remoteip' => $request->ip(),
        //     ],
        // ]);

        // $body = json_decode((string)$response->getBody());
        // if (!$body->success) {
        //     return response()->json([
        //         'status' => false,
        //         'message' => 'reCAPTCHA không hợp lệ.'
        //     ], 422);
        // }

        $admin = Admin::where('username', $request->username)->first();

        if (!$admin) {
            return response()->json([
                'status' => false,
                'message' => 'Username does not exist.'
            ], 404);
        }

        if (!Hash::check($request->password, $admin->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Incorrect password.'
            ], 401);
        }

        $now = date('d-m-Y H:i:s');
        $stringTime = strtotime($now);

        $admin->lastlogin = $stringTime;
        $admin->save();

        $token = $admin->createToken('Admin')->accessToken;

        return response()->json([
            'status' => true,
            'token' => $token,
            'display_name' => $admin->display_name
        ]);
    }

    public function logout($request)
    {
        $user = $request->user();

        if ($user) {
            $tokenId = $user->token()->id;

            $tokenRepository = app(TokenRepository::class);
            $tokenRepository->revokeAccessToken($tokenId);

            $refreshTokenRepository = app(RefreshTokenRepository::class);
            $refreshTokenRepository->revokeRefreshTokensByAccessTokenId($tokenId);

            return response()->json([
                'status' => true,
                'message' => 'Đăng xuất thành công'
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'Người dùng không hợp lệ'
        ], 401);
    }

    public function edit($id)
    {
        if(Gate::allows('THÔNG TIN QUẢN TRỊ.Quản lý tài khoản admin.edit')){
            $user = Admin::with('roles')->where('id',$id)->first();
            return response()->json([
                'status' => true,
                'admin_detail' => [
                    'username' => $user->username,
                    'email' => $user->email,
                    'display_name' => $user->display_name,
                    'avatar' => $user->avatar,
                    'phone' => $user->phone,
                ],
            ]);
        }else {
            return response()->json([
                'status'=>false,
                'mess' => 'No permission',
            ]);
        }
    }

    public function information(){
        $id = Auth::guard('admin')->user()->id;
        $user = Admin::where('id',$id)->first();
        return response()->json([
            'status'=>true,
            'admin_detail' => [
                'username' => $user->username,
                'email' => $user->email,
                'display_name' => $user->display_name,
                'avatar' => $user->avatar,
                'phone' => $user->phone,
            ],
        ]);

    }

    public function update($request)
    {
        $userAdmin = auth('admin')->user();
        if (!$userAdmin) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'email' => 'email',
            'display_name' => 'string',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'phone' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()           
            ], 422);
        }

        if ($request->has('email')) {
            $userAdmin->email = $request->email;
        }

        if ($request->has('display_name')) {
            $userAdmin->display_name = $request->display_name;
        }

        if ($request->has('phone')) {
            $userAdmin->phone = $request->phone;
        }

        //upload image
        if ($request->hasFile('avatar')) {
            $avatarFile = $request->file('avatar');

            $uploadDir = public_path('uploads' . DIRECTORY_SEPARATOR . 'admin');
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $fileName = uniqid() . '.' . $avatarFile->getClientOriginalExtension();
            $filePath = 'uploads/admin/' . $fileName;
            $avatarFile->move($uploadDir, $fileName);

            if ($userAdmin->avatar && file_exists(public_path($userAdmin->avatar))) {
                unlink(public_path($userAdmin->avatar));
            }

            $userAdmin->avatar = $filePath;
        } elseif ($request->has('avatar') && is_string($request->avatar) && str_contains($request->avatar, ';base64,')) {
            $file_chunks = explode(';base64,', $request->avatar);
            $fileType = explode('image/', $file_chunks[0] ?? '');

            if (isset($file_chunks[1], $fileType[1])) {
                $base64Img = base64_decode($file_chunks[1]);
                $imageType = $fileType[1];

                $uploadDir = public_path('uploads' . DIRECTORY_SEPARATOR . 'admin');
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $fileName = uniqid() . '.' . $imageType;
                $filePath = 'uploads/admin/' . $fileName;
                file_put_contents($uploadDir . DIRECTORY_SEPARATOR . $fileName, $base64Img);

                if ($userAdmin->avatar && file_exists(public_path($userAdmin->avatar))) {
                    unlink(public_path($userAdmin->avatar));
                }

                $userAdmin->avatar = $filePath;
            }
        }

        $userAdmin->save();

        return response()->json([
            'status' => true,
            'message' => "success"
        ]);
    }
    public function delete($id)
    {
        abort_if(!$this->permissionPolicy->hasPermission($this->user, 'THÔNG TIN QUẢN TRỊ.Quản lý tài khoản admin.del'), 403, "No permission");

        $admin = Admin::find($id);

        if (!$admin) {
            return response()->json([
                'status' => false,
                'message' => 'Admin not found'
            ], 404);
        }

        $admin->delete();

        return response()->json([
            'status' => true,
            'message' => 'Success'
        ]);
    }

    public function manage($request)
    {
        abort_if(!$this->permissionPolicy->hasPermission($this->user, 'THÔNG TIN QUẢN TRỊ.Quản lý tài khoản admin.manage'), 403, "No permission");
    
        $query = Admin::with('roles')->orderBy('id', 'desc');
    
        $role_Id = $request->input('role_id');
        $data = $request->input('data');
    
        if ($role_Id && $role_Id != 'all') {
            $listAdminId = DB::table('admin_role')
                ->where('role_id', $role_Id)
                ->join('admin', 'admin.id', '=', 'admin_role.admin_id')
                ->select('admin.id')
                ->pluck('id');
    
            if (count($listAdminId) > 0) {
                $query = $query->whereIn('id', $listAdminId);
            } else {
                return response()->json([
                    'status' => true,
                    'adminList' => [],
                ]);
            }
        }
    
        if ($data && $data != 'undefined' && $data != "") {
            $query = $query->where(function ($q) use ($data) {
                $q->where('username', 'like', '%' . $data . '%')
                    ->orWhere('email', 'like', '%' . $data . '%');
            });
        }
    
    
        return response()->json([
            'status' => true,
            'adminList' => $query->paginate(20),
        ]);
    }

    
}
