<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\Role;
use App\Models\File;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

use Gate;

class RoleController extends Controller {
    public function index(Request $request) {
        try {
            $nows = now()->timestamp;
            DB::table('adminlogs')->insert([
                'admin_id' => Auth::guard('admin')->user()->id,
                'time' => $nows,
                'ip'=> $request->ip(),
                'action'=>'show all role',
                'cat'=>'role',
            ]);

            if (Gate::allows('THÔNG TIN QUẢN TRỊ.Quản lý nhóm admin.manage')) {
                $query = Role::query();

                if ($request->data != 'undefined' && $request->data != '') {
                    $query->where(function($q) use ($request) {
                        $q->where('title', 'like', '%' . $request->data . '%')
                        ->orWhere('name', 'like', '%' . $request->data . '%');
                    });
                }

                $perPage = $request->input('per_page', 10); // Default 10 items per page
                $roles = $query->orderBy('id', 'asc')->paginate($perPage);

                return response()->json([
                    'status' => true,
                    'roles' => $roles->items(),
                    'pagination' => [
                        'current_page' => $roles->currentPage(),
                        'total_pages' => $roles->lastPage(),
                        'per_page' => $roles->perPage(),
                        'total' => $roles->total(),
                    ]
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'mess' => 'no permission',
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 422);
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
                if ($request->has('permission_id')) {
                    $role->permissions()->sync($request->input('permission_id'));
                }
                //$role->permissions()->sync( $request->input( 'permission_id', [] ) );

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
        if (!Gate::allows('THÔNG TIN QUẢN TRỊ.Quản lý nhóm admin.del')) {
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
                'message' => $e->getMessage() 
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
            if ( Gate::allows( 'THÔNG TIN QUẢN TRỊ.Quản lý nhóm admin.del' ) ) {
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