<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Gate;

class RoleController extends Controller {
    /**
    * Display a listing of the resource.
    */

    public function index( Request $request ) {
        try {

            $now = date( 'd-m-Y H:i:s' );
            $stringTime = strtotime( $now );
            DB::table( 'adminlogs' )->insert( [
                'admin_id' => Auth::guard( 'admin' )->user()->id,
                'time' =>  $stringTime,
                'ip'=> $request->ip(),
                'action'=>'show all role',
                'cat'=>'role',
            ] );
            if ( Gate::allows( 'THÔNG TIN QUẢN TRỊ.Quản lý nhóm admin.manage' ) ) {
                if ( $request->data == 'undefined' || $request->data == '' ) {
                    $roles = Role::orderBy( 'id', 'desc' )->get();
                } else {
                    $roles = Role::where( 'title', 'like', '%' . $request->data . '%' )
                    ->orWhere( 'name', 'like', '%' . $request->data . '%' )
                    ->orderBy( 'id', 'desc' )
                    ->get();
                }
                return response()->json( [
                    'status'=>true,
                    'roles'=>$roles
                ] );
            } else {
                return response()->json( [
                    'status'=>false,
                    'mess' => 'no permission',
                ] );
            }
        } catch( \Exception $e ) {
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
        //
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
                'ip'=> $request->ip(),
                'action'=>'add a role',
                'cat'=>'role',
            ] );
            if ( Gate::allows( 'THÔNG TIN QUẢN TRỊ.Quản lý nhóm admin.add' ) ) {
                $role = Role::create( [
                    'name'=>$request->input( 'name' ),
                    'title'=>$request->input( 'title' ),
                    'description'=>$request->input( 'description' )
                ] );
                $role->permissions()->attach( $request->input( 'permission_id' ) );
                return response()->json( [
                    'status'=>true,
                    'message'=>'create Role success'
                ] );
            } else {
                return response()->json( [
                    'status'=>false,
                    'mess' => 'no permission',
                ] );
            }
        } catch( \Exception $e ) {
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

    public function edit( Request $request, string $id ) {
        try {

            $now = date( 'd-m-Y H:i:s' );
            $stringTime = strtotime( $now );
            DB::table( 'adminlogs' )->insert( [
                'admin_id' => Auth::guard( 'admin' )->user()->id,
                'time' =>  $stringTime,
                'ip'=> $request->ip(),
                'action'=>'edit a role',
                'cat'=>'role',
            ] );
            if ( Gate::allows( 'THÔNG TIN QUẢN TRỊ.Quản lý nhóm admin.edit' ) ) {
                $role = Role::find( $id );
                $permission = DB::table( 'role_permission' )
                ->where( 'role_id', $role->id )->pluck( 'permission_id' );

                return response()->json( [
                    'status'=>true,
                    'role'=>$role,
                    'permissions'=>$permission
                ] );
            } else {
                return response()->json( [
                    'status'=>false,
                    'mess' => 'no permission',
                ] );
            }
        } catch( \Exception $e ) {
            return response()->json( [
                'status' => false,
                'message' => $e->getMessage()
            ], 422 );
        }
    }

    /**
    * Update the specified resource in storage.
    */

    public function update( Request $request, string $id ) {
        try {
            $now = date( 'd-m-Y H:i:s' );
            $stringTime = strtotime( $now );
            DB::table( 'adminlogs' )->insert( [
                'admin_id' => Auth::guard( 'admin' )->user()->id,
                'time' =>  $stringTime,
                'ip'=> $request->ip(),
                'action'=>'update a role',
                'cat'=>'role',
            ] );

            if ( Gate::allows( 'THÔNG TIN QUẢN TRỊ.Quản lý nhóm admin.update' ) ) {
                $role = Role::find( $id );
                $role->update( [
                    'name'=>$request->input( 'name' ),
                    'title'=>$request->input( 'title' ),
                    'description'=>$request->input( 'description' )
                ] );
                $role->permissions()->sync( $request->input( 'permission_id', [] ) );
                return response()->json( [
                    'status'=>true,
                    'message'=>'update Role success'
                ] );
            } else {
                return response()->json( [
                    'status'=>false,
                    'mess' => 'no permission',
                ] );
            }
        } catch( \Exception $e ) {
            return response()->json( [
                'status' => false,
                'message' => $e->getMessage()
            ], 422 );
        }
    }

    /**
    * Remove the specified resource from storage.
    */

    public function deleteAll( Request $request ) {
        $arr = $request->data;
        //return $arr;
        try {
            // if ( Gate::allows( 'THÔNG TIN QUẢN TRỊ.Quản lý nhóm admin.del' ) ) {
            if ( $arr ) {
                foreach ( $arr as $item ) {
                    $role = Role::find( $item );
                    $role->delete();
                }
            } else {
                return response()->json( [
                    'status'=>false,
                ], 422 );
            }
            return response()->json( [
                'status'=>true,
            ], 200 );
            // } else {
            //     return response()->json( [
            //         'status'=>false,
            //         'mess' => 'no permission',
            // ] );
            // }
        } catch ( \Exception $e ) {
            $errorMessage = $e->getMessage();
            $response = [
                'status' => 'false',
                'error' => $errorMessage
            ];
            return response()->json( $response, 500 );
        }
    }

    public function destroy( Request $request, string $id ) {
        try {

            $now = date( 'd-m-Y H:i:s' );
            $stringTime = strtotime( $now );
            DB::table( 'adminlogs' )->insert( [
                'admin_id' => Auth::guard( 'admin' )->user()->id,
                'time' =>  $stringTime,
                'ip'=> $request->ip(),
                'action'=>'delete a role',
                'cat'=>'role',
            ] );
            if ( Gate::allows( 'THÔNG TIN QUẢN TRỊ.Quản lý nhóm admin.del' ) ) {
                $role = Role::find( $id );
                $role->delete();
                return response()->json( [
                    'status'=>true,
                    'message'=>'Delete Role success'
                ] );
            } else {
                return response()->json( [
                    'status'=>false,
                    'mess' => 'no permission',
                ] );
            }
        } catch( \Exception $e ) {
            return response()->json( [
                'status' => false,
                'message' => $e->getMessage()
            ], 422 );
        }
    }
}
