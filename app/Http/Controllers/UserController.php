<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\QueryBuilder\QueryBuilder;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Serializer\ArraySerializer;
use App\Transformers\UserTransformer;
use League\Fractal\Resource\Collection;
use Spatie\Fractal\Fractal;
use App\Imports\ImportUsers;
use Maatwebsite\Excel\Facades\Excel;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = User::all();

        $users = QueryBuilder::for(User::class)
            ->allowedFilters(['name', 'email', 'id']);

        $pagination = $users->paginate(2);
        $users = $pagination->appends(request()->query());

        $response = Fractal::create()
            ->collection($users, new UserTransformer())
            ->serializeWith(new ArraySerializer)
            ->paginateWith(new IlluminatePaginatorAdapter($pagination))
            ->toArray();

        return response()->json([
            'success' => true,
            'data' => $response
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'email' => 'required',
            'password' => 'required'
        ]);

        $user = User::create($request->all());

        if ($user)
            return response()->json([
                'success' => true,
                'data' => $user->toArray()
            ]);
        else
            return response()->json([
                'success' => false,
                'message' => 'Post not added'
            ], 500);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found '
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => $user->toArray()
        ], 400);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'user not found'
            ], 400);
        }

        $this->validate($request, [
            'name' => 'required',
            'email' => 'required',
            'password' => 'required'

        ]);

        $userData = $request->only(["email", "password", "name", "imported"]);
        $userData['password'] = Hash::make($userData['password']);
        $updated = $user->fill($userData)->save();

        if ($updated)
            return response()->json([
                'success' => true,
                'data' => $user->toArray()
            ]);
        else
            return response()->json([
                'success' => false,
                'message' => 'user can not be updated'
            ], 500);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'user not found'
            ], 400);
        }

        if ($user->delete()) {
            return response()->json([
                'success' => true,
                'message' => 'user is deleted'
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'user can not be deleted'
            ], 500);
        }
    }

    public function import(Request $request)
    {
        $request->validate([
            'import_file' => 'required|mimes:xlsx,csv|max:2048'
        ]);

        //complete
        if ($request->file('import_file')) {
            Excel::import(new ImportUsers, request()->file('import_file'));

            return response()->json([
                'success' => true,
                'import' => 'success',
            ]);
        } else {
            return response()->json([
                'success' => false,
                'key' => ' as import_file'
            ]);
        }
    }
}
