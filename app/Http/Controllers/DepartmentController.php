<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Department;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Gate;

class DepartmentController extends Controller {
    /**
    * Display a listing of the resource.
    */

    public function index() {
        try {
            $department = Department::get();
            return response()->json( [
                'status'=>true,
                'deparment'=>$department
            ] );
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
            $data = $request->all();
            $validator = Validator::make( $request->all(), [
                'name' => 'required',
            ] );
            if ( $validator->fails() ) {
                return response()->json( [
                    'message'=>'Validations fails',
                    'errors'=>$validator->errors()
                ], 422 );
            }

            $check = Department::where( 'name', $data[ 'name' ] )->first();
            if ( $check != '' ) {
                return response()->json( [
                    'message'=>'name',
                    'status'=>'false'
                ], 202 );
            }
            $derpartment = new Department();
            $derpartment->name = $data[ 'name' ];
            $derpartment->save();

            return response()->json( [
                'status' => true,
                'data'=>$derpartment
            ] );
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

    public function edit( string $id ) {
        try {
            $data = Department::find( $id );
            return response()->json( [
                'status'=>true,
                'data'=>$data
            ] );
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

    public function update( Request $request, int $id ) {
        try {
            $validator = Validator::make( $request->all(), [
                'name' =>'required',
            ] );
            if ( $validator->fails() ) {
                return response()->json( [
                    'message'=>'Validations fails',
                    'errors'=>$validator->errors()
                ], 422 );
            }
            $data = $request->all();

            $check = Department::where( 'name', $data[ 'name' ] )->where( 'id', '!=', $id )->first();
            if ( $check != '' ) {
                return response()->json( [
                    'message'=>'name',
                    'status'=>'false'
                ], 202 );
            }
            $derpartment = Department::find( $id );
            $derpartment->name = $data[ 'name' ];
            $derpartment->save();
            return response()->json( [
                'status' => true,
            ] );
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

    public function destroy( string $id ) {
        try {
            $derpartmentId = Department::find( $id );
            $derpartmentId->delete();
            return response()->json( [
                'status' => true
            ] );
        } catch( \Exception $e ) {
            return response()->json( [
                'status' => false,
                'message' => $e->getMessage()
            ], 422 );
        }
    }
}
