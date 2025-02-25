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
    /**
    * Display a listing of the resource.
    */

    public function dashboard() {
        return 111;
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

            // return Auth::guard( 'admin' )->user();
            $now = date( 'd-m-Y H:i:s' );
            $stringTime = strtotime( $now );
            DB::table( 'adminlogs' )->insert( [
                'admin_id' => Auth::guard( 'admin' )->user()->id,
                'time' =>  $stringTime,
                'ip' => $request->ip() ?? null,
                'action' => 'show all admin',
                'cat' => 'admin',
            ] );
            // return  DB::table( 'adminlogs' )->get();

            $index = $this->adminService->index( $request );
            return $index;
        } catch ( \Exception $e ) {
            return response()->json( [
                'status' => false,
                'message' => $e->getMessage()
            ], 422 );
        }
    }

    /**
    * Show the form for creating a new resource.
    */

    public function create() {
    }

    /**
    * Store a newly created resource in storage.
    */

    public function store( Request $request ) {
        try {

            $now = date( 'd-m-Y H:i:s' );
            $stringTime = strtotime( $now );
            DB::table( 'adminlogs' )->insert( [
                'admin_id' => Auth::guard( 'admin' )->user()->id,
                'time' =>  $stringTime,
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

    /**
    * Display the specified resource.
    */

    public function show( string $id ) {
        //
    }

    /**
    * Show the form for editing the specified resource.
    */

    public function edit( Request $request, int $id ) {
        try {
            $now = date( 'd-m-Y H:i:s' );
            $stringTime = strtotime( $now );
            DB::table( 'adminlogs' )->insert( [
                'admin_id' => Auth::guard( 'admin' )->user()->id,
                'time' =>  $stringTime,
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

    /**
    * Update the specified resource in storage.
    */

    public function update( Request $request, int $id ) {
        try {
            $now = date( 'd-m-Y H:i:s' );
            $stringTime = strtotime( $now );
            DB::table( 'adminlogs' )->insert( [
                'admin_id' => Auth::guard( 'admin' )->user()->id,
                'time' =>  $stringTime,
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

    /**
    * Remove the specified resource from storage.
    */

    public function destroy( Request $request, int $id ) {
        try {

            $now = date( 'd-m-Y H:i:s' );
            $stringTime = strtotime( $now );
            DB::table( 'adminlogs' )->insert( [
                'admin_id' => Auth::guard( 'admin' )->user()->id,
                'time' =>  $stringTime,
                'ip' => $request ? $request->ip() : null,
                'action' => 'delete a admin',
                'cat' => 'admin',
            ] );
            $destroy = $this->adminService->destroy( $id );
            return $destroy;
        } catch ( \Exception $e ) {
            return response()->json( [
                'status' => false,
                'message' => $e->getMessage()
            ], 422 );
        }
    }

    public function deleteAll( Request $request ) {
        $arr = $request->data;
        //return $arr;
        try {
            if ( Gate::allows( 'THÔNG TIN QUẢN TRỊ.Quản lý tài khoản admin.del' ) ) {
                if ( $arr ) {
                    foreach ( $arr as $item ) {

                        Admin::where( 'id', $item )->delete();
                    }
                } else {
                    return response()->json( [
                        'status' => false,
                    ], 422 );
                }
                return response()->json( [
                    'status' => true,
                ], 200 );
            } else {
                return response()->json( [
                    'status' => false,
                    'mess' => 'no permission',
                ] );
            }
        } catch ( \Exception $e ) {
            $errorMessage = $e->getMessage();
            $response = [
                'status' => 'false',
                'error' => $errorMessage
            ];
            return response()->json( $response, 500 );
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
}