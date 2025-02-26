<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Gate;

class RoleController extends Controller {
    public function index( Request $request ) {
        try {
            $nows = now()->timestamp;
            DB::table( 'adminlogs' )->insert( [
                'admin_id' => Auth::guard( 'admin' )->user()->id,
                'time' =>  $nows,
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

    public function create() {
    }

    public function store( Request $request ) {
        try {
            $nows = now()->timestamp;
            DB::table( 'adminlogs' )->insert( [
                'admin_id' => Auth::guard( 'admin' )->user()->id,
                'time' =>  $nows,
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

    public function show( string $id ) {
    }

    public function edit( Request $request, string $id ) {
        try {

            $nows = now()->timestamp;
            DB::table( 'adminlogs' )->insert( [
                'admin_id' => Auth::guard( 'admin' )->user()->id,
                'time' =>  $nows,
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

    public function update( Request $request, string $id ) {
        try {
            $nows = now()->timestamp;
            DB::table( 'adminlogs' )->insert( [
                'admin_id' => Auth::guard( 'admin' )->user()->id,
                'time' =>  $nows,
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

    public function delete(Request $request)
    {
        if (!Gate::allows('THÔNG TIN QUẢN TRỊ.Quản lý nhóm admin.delete')) {
            return response()->json([
                'status' => false,
                'message' => 'no permission',
            ], 403);
        }

        try {
            $request->validate([
                'ids' => 'required|array',
                'ids*' => 'exists:roles,id',
            ]);

            $ids = $request->input('ids');

            if (is_array($ids)) {
                $ids = implode(",", $ids);
            }

            $idsArray = explode(",", $ids);

            foreach ($idsArray as $id) {
                Role::whereIn('id', $idsArray)->delete();
            }

            $admin = Auth::guard('admin')->user();

            $nows = now()->timestamp;
            DB::table('adminlogs')->insert([
                'admin_id' => $admin->id,
                'time' => $now,
                'ip' => $request->ip() ?? null,
                'action' => 'delete business group',
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

    public function destroy( Request $request, string $id ) {
        try {
            $nows = now()->timestamp;
            DB::table( 'adminlogs' )->insert( [
                'admin_id' => Auth::guard( 'admin' )->user()->id,
                'time' =>  $nows,
                'ip'=> $request->ip(),
                'action'=>'delete a role',
                'cat'=>'role',
            ] );
            if ( Gate::allows( 'THÔNG TIN QUẢN TRỊ.Quản lý nhóm admin.delete' ) ) {
                $role = Role::find( $id );
                $role->delete();
                return response()->json( [
                    'status'=>true,
                    'message'=>'delete Role success'
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