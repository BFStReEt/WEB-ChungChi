<?php

namespace App\Services;

use App\Services\Interfaces\AdminServiceInterface;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Laravel\Passport\Passport;

use App\Repositories\Interfaces\AdminRepositoryInterface as AdminRepository;

class AdminService implements AdminServiceInterface
{
    public function login($request){
        $val = Validator::make($request->all(), [
            'username' => 'required',
            'password' => 'required',
        ]);
        if ($val->fails()) {
            return response()->json($val->errors(), 202);
        }
        $nows = now()->timestamp;
        $admin = Admin::where('username', $request->username)->first();

        if (isset($admin) != 1) {
            return response()->json([
                'status' => false,
                'mess' => 'username'
            ], 401);
        }

        $check =  $admin->makeVisible('password');

        if (Hash::check($request->password, $check->password)) {
            $success = $admin->createToken('Admin')->accessToken;

            $admin->lastlogin = $nows;
            $admin->save();

            return response()->json([
                'status' => true,
                'token' => $success,
                'username' => $admin->display_name
            ]);
        } else{
            return response()->json([
                'status' => false,
                'mess' => 'password'
            ], 401);
        }
    }

    public function information(){
        $id = Auth::guard( 'admin' )->user()->id;
        $userAdmin = Admin::where( 'id', $id )->first();
        return response()->json( [
            'status' => true,
            'data' => $userAdmin,
        ] );
    }

    public function logout(){
        Auth::guard( 'admin' )->user()->token()->revoke();
        return response()->json( [
            'status' => true
        ] );
    }

    public function index($request) {
        $adminUser = Auth::guard('admin')->user();
        if (!$adminUser) {
            return response()->json([
                'status' => false,
                'mess' => 'User not authenticated',
            ], 401);
        }

        if (Gate::allows('THÔNG TIN QUẢN TRỊ.Quản lý tài khoản admin.manage')) {
            $query = Admin::with('roles')->orderBy('id', 'asc');
            $roleId = $request->role_id;
            $listAdminId = DB::table('admin_role')->where('role_id', $roleId)
                ->join('admin', 'admin.id', '=', 'admin_role.admin_id')
                ->select('admin.*')->pluck('id');

            if (isset($roleId)) {
                $listAdminId = DB::table('admin_role')->where('role_id', $roleId)
                    ->join('admin', 'admin.id', '=', 'admin_role.admin_id')
                    ->select('admin.*')->pluck('id');
                if (count($listAdminId) != 0) {
                    $query = Admin::with('roles')->whereIn('id', $listAdminId);
                } else {
                    return response()->json([
                        'status' => true,
                        'adminList' => [],
                    ]);
                }
            }

            if ($request->data == 'undefined' || $request->data == '') {
                $list = $query;
            } else {
                $list = $query->where('username', 'like', '%' . $request->data . '%')
                    ->orWhere('email', 'like', '%' . $request->data . '%');
            }

             $users = $query->orderBy( 'id', 'asc' )->paginate( 5 );

            $formattedUsers = $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'display_name' => $user->display_name,
                    'avatar' => $user->avatar,
                    'phone' => $user->phone,
                    'last_login' => date('d-m-Y, h:i:s A', $user->lastlogin),
                    'roles' => $user->roles->map(function($role){
                        return $role->name;
                    })->implode(', '),
                ];
            });

            return response()->json([
                'status' => true,
                'data' => $formattedUsers,
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'total_pages' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                ],
            ]);
        }
    }
    
    public function store( $request ){
        if ( Gate::allows( 'THÔNG TIN QUẢN TRỊ.Quản lý tài khoản admin.add' ) ) {
            $validator = Validator::make( $request->all(), [
                'username' => 'required',
                'password' => 'required',
            ] );
            if ( $validator->fails() ) {
                return response()->json( [
                    'message' => 'Vui lòng nhập tên đăng nhập và mật khẩu',
                    'errors' => $validator->errors()
                ], 422 );
            }
            $check = Admin::where( 'username', $request->username )->first();
            if ( $check != '' ) {
                return response()->json( [
                    'message' => 'Tên đăng nhập bị trùng ,vui lòng nhập lại',
                    'status' => 'false'
                ], 202 );
            }
            $data = $request->only( [
                'username',
                'password',
                'email',
                'display_name',
                'avatar',
                'phone',
                'status',
                'depart_id',
            ] );
            $userAdmin = new Admin();
            $userAdmin->username = $request[ 'username' ];
            $userAdmin->password = Hash::make( $request[ 'password' ] );
            $userAdmin->email = $request[ 'email' ];
            $userAdmin->display_name = $request[ 'display_name' ];
            //$userAdmin -> avatar = isset( $request[ 'avatar' ] ) ? $request[ 'avatar' ] : null;

            $filePath = '';
            $disPath = public_path();

            if ($request->hasFile('avatar')) {
                $file = $request->file('avatar');
                $name = uniqid() . '.' . $file->getClientOriginalExtension();
                $filePath = 'admin/' . $name;
                $file->move(public_path('uploads/admin'), $name);
                $userAdmin->avatar = $filePath;
            }

            $userAdmin->skin = '';
            $userAdmin->is_default = 0;
            $userAdmin->lastlogin = 0;
            $userAdmin->code_reset = Hash::make( $request[ 'password' ] );
            $userAdmin->menu_order = 0;
            $userAdmin->phone = $request[ 'phone' ];
            $userAdmin->status = $request[ 'status' ];
            $userAdmin->depart_id = $request[ 'depart_id' ];
            $userAdmin->roles()->attach( $request->input( 'role_id' ) );
            
            $userAdmin->save();
            
            return response()->json( [
                'status' => true,
                'userAdmin' => $userAdmin,
            ] );
        } else{
            return response()->json( [
                'status' => false,
                'mess' => 'no permission',
            ] );
        }
    }

    public function edit( $id ){
        if ( Gate::allows( 'THÔNG TIN QUẢN TRỊ.Quản lý tài khoản admin.edit' ) ) {
            $userAdminDetail = Admin::with( 'roles' )->where( 'id', $id )
            ->first();
            return response()->json( [
                'status' => true,
                'userAdminDetail' => $userAdminDetail,
            ] );
        } else {
            return response()->json( [
                'status' => false,
                'mess' => 'no permission',
            ] );
        }
    }

    public function update( $request, $id ){
        if ( !Gate::allows( 'THÔNG TIN QUẢN TRỊ.Quản lý tài khoản admin.update')){   
            return response()->json( [
                'status' => false,
                'mess' => 'no permission',
            ] );
        }
        $userAdmin = Admin::where( 'id', $id )->first();
        if ( !isset( $userAdmin ) ) {
            return response()->json( [
                'message' => 'name',
                'status' => 'false'
            ], 202 );
        }

        $userAdmin->email = $request[ 'email' ] ? $request[ 'email' ] : $userAdmin->email;
        $userAdmin->display_name = $request[ 'display_name' ] ? $request[ 'display_name' ] : $userAdmin->display_name;
        $userAdmin->phone = $request[ 'phone' ] ? $request[ 'phone' ] : $userAdmin->phone;
        $userAdmin->status = $request[ 'status' ] ? $request[ 'status' ] : $userAdmin->status;
            
        $filePath = '';
        $disPath = public_path();
        if ($request->hasFile('avatar') && $userAdmin->avatar != $request->avatar) {
            $file = $request->file('avatar');
            $name = uniqid() . '.' . $file->getClientOriginalExtension();
            $filePath = 'admin/' . $name;
            $file->move(public_path('uploads/admin'), $name);
            $userAdmin->avatar = $filePath;
        } else {
            $filePath =  $userAdmin->avatar;
        }
        $userAdmin->avatar = $filePath;
        if ($request->has('role_id')) {
             $userAdmin->roles()->sync($request->input('role_id'));
        }
        $userAdmin->save();
        return response()->json( [
            'status' => true,
            'displayName' => $userAdmin,
        ] );
    }

    public function destroy( $id ){
        if ( Gate::allows( 'THÔNG TIN QUẢN TRỊ.Quản lý tài khoản admin.destroy' ) ) {
            Admin::where( 'id', $id )->delete();
            return response()->json( [
                'status' => true
            ] );
        } else {
            return response()->json( [
                'status' => false,
                'mess' => 'no permission',
            ] );
        }
    }
}