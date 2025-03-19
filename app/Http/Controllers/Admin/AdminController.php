<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Gate;
use App\Services\Interfaces\AdminServiceInterface as AdminService;

class AdminController extends Controller {
    protected $adminService;

    public function __construct( AdminService $adminService ) {
        $this->adminService = $adminService;
    }

    public function login( Request $request ) {
        try {
            $login = $this->adminService->login( $request );
            return $login;
        } catch ( \Exception $e ) {
            return response()->json( [
                'status' => false,
                'message' => $e->getMessage()
            ], 422 );
        }
    }

    public function information() {
        try {

            $information = $this->adminService->information();
            return $information;
            // $id = Auth::guard( 'admin' )->user()->id;
            // $userAdmin = Admin::where( 'id', $id )->first();
            // return response()->json( [
            //    'status'=>true,
            //    'data'=> $userAdmin,
            // ] );
        } catch ( \Exception $e ) {
            return response()->json( [
                'status' => false,
                'message' => $e->getMessage()
            ], 422 );
        }
    }

    public function logout() {

        try {
            $logout = $this->adminService->logout();
            return $logout;
        } catch ( \Exception $e ) {
            return response()->json( [
                'status' => false,
                'message' => $e->getMessage()
            ], 422 );
        }
    }

    public function index( Request $request ) {
        try {
            $nows = now()->timestamp;
            DB::table( 'adminlogs' )->insert( [
                'admin_id' => Auth::guard( 'admin' )->user()->id,
                'time' =>  $nows,
                'ip' => $request->ip() ?? null,
                'action' => 'show all admin',
                'cat' => 'admin',
            ] );

            $index = $this->adminService->index( $request );
            return $index;
        } catch ( \Exception $e ) {
            return response()->json( [
                'status' => false,
                'message' => $e->getMessage()
            ], 422 );
        }
    }

    public function create() {
    }

    public function store( Request $request ) {
        try {
            $nows = now()->timestamp;
            DB::table( 'adminlogs' )->insert( [
                'admin_id' => Auth::guard( 'admin' )->user()->id,
                'time' =>  $nows,
                'ip' => $request->ip() ?? null,
                'action' => 'add a admin',
                'cat' => 'admin',
            ] );
            $store = $this->adminService->store( $request );
            return $store;
        } catch ( \Exception $e ) {
            return response()->json( [
                'status' => false,
                'message' => $e->getMessage()
            ], 422 );
        }
    }

    public function show( string $id ) {}

    public function edit( Request $request, int $id ) {
        try {
            $nows = now()->timestamp;
            DB::table( 'adminlogs' )->insert( [
                'admin_id' => Auth::guard( 'admin' )->user()->id,
                'time' =>  $nows,
                'ip' => $request->ip() ?? null,
                'action' => 'edit a admin',
                'cat' => 'admin',
            ] );

            $edit = $this->adminService->edit( $id );
            return $edit;
        } catch ( \Exception $e ) {
            return response()->json( [
                'status' => false,
                'message' => $e->getMessage()
            ], 422 );
        }
    }

    public function update( Request $request, int $id ) {
        try {
            $nows = now()->timestamp;
            DB::table( 'adminlogs' )->insert( [
                'admin_id' => Auth::guard( 'admin' )->user()->id,
                'time' =>  $nows,
                'ip' => $request->ip() ?? null,
                'action' => 'update a admin',
                'cat' => 'admin',
            ] );
            $update = $this->adminService->update( $request, $id );
            return $update;
        } catch ( \Exception $e ) {
            return response()->json( [
                'status' => false,
                'message' => $e->getMessage()
            ], 422 );
        }
    }

    public function destroy( Request $request, int $id ) {
        try {
            if ( Gate::allows( 'THÔNG TIN QUẢN TRỊ.Quản lý tài khoản admin.del' ) ) {
                $now = date( 'd-m-Y H:i:s' );
                $nows = now()->timestamp;
                DB::table( 'adminlogs' )->insert( [
                    'admin_id' => Auth::guard( 'admin' )->user()->id,
                    'time' =>  $nows,
                    'ip' => $request ? $request->ip() : null,
                    'action' => 'delete a admin',
                    'cat' => 'admin',
                ] );
                $destroy = $this->adminService->destroy( $id );
                return $destroy;
            }
            return response()->json( [
                'status' => false,
                'mess' => 'no permission',
            ] );
        } catch ( \Exception $e ) {
            return response()->json( [
                'status' => false,
                'message' => $e->getMessage()
            ], 422 );
        }
    }

    public function delete(Request $request)
    {
        if (!Gate::allows('THÔNG TIN QUẢN TRỊ.Quản lý tài khoản admin.del')) {
            return response()->json([
                'status' => false,
                'message' => 'no permission',
            ], 403);
        }
        try {
            $request->validate([
                'ids' => 'required|array',
                'ids*' => 'exists:admin,id',
            ]);

            $ids = $request->input('ids');

            if (is_array($ids)) {
                $ids = implode(",", $ids);
            }

            $idsArray = explode(",", $ids);

            foreach ($idsArray as $id) {
                Admin::whereIn('id', $idsArray)->delete();
            }

            $admin = Auth::guard('admin')->user();

            $nows = now()->timestamp;
            DB::table('adminlogs')->insert([
                'admin_id' => $admin->id,
                'time' => $now,
                'ip' => $request->ip() ?? null,
                'action' => 'delete admin',
                'cat' => $admin->display_name,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'success'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Lỗi khi xóa dữ liệu'
            ], 500);
        }
    }

    public function log( Request $request ) {
        if ( Gate::allows( 'THÔNG TIN QUẢN TRỊ.Lịch sử hoạt động admin.manage' ) ) {
            $adminLog = DB::table( 'adminlogs' )
            ->join( 'admin', 'admin.id', '=', 'adminlogs.admin_id' )
            ->select( 'adminlogs.*', 'admin.username', 'admin.display_name' );
            if ( $request->input( 'data' ) !== null && $request->input( 'data' ) !== 'all' ) {
                $adminLog->where( 'cat', $request->input( 'data' ) );
            }
            if ( $request->input( 'action' ) !== null && $request->input( 'action' ) !== 'all' ) {
                $adminLog->where( 'action', $request->input( 'action' ) );
            }
            if ( $request->input( 'username' ) !== null ) {
                $adminLog->where( 'username', 'like', '%' . $request->input( 'username' ) . '%' );
            }

            if ( $request->input( 'fromDate' ) && $request->input( 'toDate' ) ) {
                // return 111;
                $fromDate = $request->input( 'fromDate' );
                $toDate = $request->input( 'toDate' );
                //return $fromDate.'-'. $toDate;
                $adminLog->whereBetween( 'time', [ $fromDate, $toDate ] );
            }
            $adminLogs = $adminLog->orderBy( 'time', 'desc' )->paginate( 10 );
            return response()->json( [
                'status' => true,
                'listLog' => $adminLogs
            ] );
        } else {
            return response()->json( [
                'status' => false,
                'mess' => 'no permission',
            ] );
        }
    }

    public function showSelectAdmin() {
        try {
            $query = Admin::select( 'id', 'username' )->orderBy( 'id', 'desc' )->get();
            return response()->json( [
                'status' => true,
                'data' => $query
            ] );
        } catch ( \Exception $e ) {
            return response()->json( [
                'status' => false,
                'message' => $e->getMessage()
            ], 422 );
        }
    }

    public function UpdateProfile(Request $request){
        $userAdmin = Auth::guard('admin')->user();
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
        $userAdmin->save();
        return response()->json( [
            'status' => true,
            'displayName' => $userAdmin,
        ] );
    }

    public function deleteMutilLog(Request $request){
        if (!Gate::allows('THÔNG TIN QUẢN TRỊ.Lịch sử hoạt động.del')) {
            return response()->json([
                'status' => false,
                'message' => 'no permission',
            ], 403);
        }

        try {
            $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'exists:roles,id',
            ]);

            $ids = $request->input('ids');

            Role::whereIn('id', $ids)->delete();

            $admin = Auth::guard('admin')->user();
            $nows = now()->timestamp;

            DB::table('adminlogs')->insert([
                'admin_id' => $admin->id,
                'time' => $nows,
                'ip' => $request->ip() ?? null,
                'action' => 'delete log',
                'cat' => $admin->display_name,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'success'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

}