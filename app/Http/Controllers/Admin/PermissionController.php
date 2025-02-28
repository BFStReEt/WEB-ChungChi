<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Permission;
use App\Models\GroupPermission;
use App\Models\Category;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Gate;
class PermissionController extends Controller
{
    public function index(Request $request){
        try{
            $nows = now()->timestamp;
            DB::table('adminlogs')->insert([
                'admin_id' => Auth::guard('admin')->user()->id,
                'time' =>  $nows,
                'ip'=> $request->ip(),
                'action'=>'show all permission',
                'cat'=>'permission',
            ]);
            if(Gate::allows('THÔNG TIN QUẢN TRỊ.Quyền hạn.manage')){
                $permissions=Permission::all()->groupBy(function ($permission){
                    return explode('.',$permission->slug)[0];
                })->map(function ($group) {
                    return $group->groupBy(function ($permission) {
                        return explode('.', $permission->slug)[1];
                    });
                });
                return response()->json([
                    'status'=>true,
                    'permissions'=>$permissions
                ]);
            } else {
                return response()->json([
                    'status'=>false,
                    'mess' => 'no permission',
                ]);
            }
        }
        catch(\Exception $e){
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function showPermission(){
        $permissions=Permission::all()->groupBy(function ($permission){
            return explode('.',$permission->slug)[0];
        })->map(function ($group) {
            return $group->groupBy(function ($permission) {
                return explode('.', $permission->slug)[1];
            });
        });
        $groupPermission=[];
        foreach($permissions as $key=> $permission){
            $childPermission=[];
            foreach($permission as $key1=> $per){
                $childPermission[]=[
                    'keyChild'=>$key1,
                    'ChildPermission'=>$per

                ];
            }
            $groupPermission[]=[
                'keyGroup'=>$key,
                'groupPermission'=>$childPermission
            ];

        }
        return  $groupPermission;
        return response()->json([
            'status'=>true,
            'permissions'=>$permissions
        ]);
    }

    public function create(){
    }
    
    public function store(Request $request){
        try{
            $nows = now()->timestamp;
            DB::table('adminlogs')->insert([
                'admin_id' => Auth::guard('admin')->user()->id,
                'time' =>  $nows,
                'ip'=> $request->ip(),
                'action'=>'add a permission',
                'cat'=>'permission',
            ]);
            if(Gate::allows('THÔNG TIN QUẢN TRỊ.Quyền hạn.add')){
            // Lấy và kiểm tra category cha
                $parentCategory = Category::find($request->input('parentCate'));
                if(!$parentCategory) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Parent category not found'
                    ], 404);
                }

                // Khởi tạo slug với tên category cha
                $slugParts = [$parentCategory->name];

                // Kiểm tra và thêm child category nếu có
                if($request->input('childCate')) {
                    $childCategory = Category::find($request->input('childCate'));
                    if($childCategory) {
                        $slugParts[] = $childCategory->name;
                    }
                }

                // Kiểm tra và thêm year category nếu có
                if($request->input('yearCate')) {
                    $yearCategory = Category::find($request->input('yearCate'));
                    if($yearCategory) {
                        $slugParts[] = $yearCategory->name;
                    }
                }

                // Thêm tên permission vào cuối
                $slugParts[] = $request->input('permissionName');

                // Tạo permission với slug được ghép từ các phần
                Permission::create([
                    'name' => $request->input('permissionName'),
                    'slug' => implode('.', $slugParts)
                ]);

                return response()->json([
                    'status' => true,
                    'message' => 'create Permission success'
                ]);
            }
        }
        catch(\Exception $e){
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function show(string $id){
    }

    public function edit(Request $request,string $id){
        try{
            $nows = now()->timestamp;
            DB::table('adminlogs')->insert([
                'admin_id' => Auth::guard('admin')->user()->id,
                'time' =>  $nows,
                'ip'=> $request->ip(),
                'action'=>'edit a permission',
                'cat'=>'permission',
            ]);

            
            if(Gate::allows('THÔNG TIN QUẢN TRỊ.Quyền hạn.edit')){
                $permission=Permission::find($id);
                return response()->json([
                    'status'=>true,
                    'permission'=>$permission,

                ]);
                return response()->json([
                    'status'=>true,
                    'message'=>'create Permission success'
                ]);
            } else {
                return response()->json([
                    'status'=>false,
                    'mess' => 'no permission',
                ]);
            }
        }
        catch(\Exception $e){
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function update(Request $request, string $id){
        try{
            $nows = now()->timestamp;
            DB::table('adminlogs')->insert([
                'admin_id' => Auth::guard('admin')->user()->id,
                'time' =>  $nows,
                'ip'=> $request->ip(),
                'action'=>'update a permission',
                'cat'=>'permission',
            ]);

            if(Gate::allows('THÔNG TIN QUẢN TRỊ.Quyền hạn.update')){
                Permission::where('id', $id)->update([
                    'name'=>$request->input('name'),
                    'slug'=>$request->input('slug'),
                    'description'=>$request->input('description'),
                    'groupPermission'=>$request->input('groupPermission')
                ]);
                return response()->json([
                    'status'=>true,
                    'message'=>'Update Permission success'
                ]);
        } else {
            return response()->json([
                'status'=>false,
                'mess' => 'no permission',
            ]);
        }
        }
        catch(\Exception $e){
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function destroy(Request $request,string $id){
        try{
            $nows = now()->timestamp;
            DB::table('adminlogs')->insert([
                'admin_id' => Auth::guard('admin')->user()->id,
                'time' =>  $nows,
                'ip'=> $request->ip(),
                'action'=>'update a permission',
                'cat'=>'permission',
            ]);
            if(Gate::allows('THÔNG TIN QUẢN TRỊ.Quyền hạn.del')){
                Permission::where('id', $id)->delete();
                return response()->json([
                    'status'=>true,
                    'message'=>'Delete Permission success'
                ]);
            } else {
                return response()->json([
                    'status'=>false,
                    'mess' => 'no permission',
                ]);
            }
        }
        catch(\Exception $e){
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }
}