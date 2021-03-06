<?php

namespace App\Api\Users;

use App\Api\Base\BaseController;
use App\Api\Users\Requests\CreateAvatarRequest;
use App\Api\Users\Requests\CreateUserRequest;
use App\Api\Users\Requests\DeleteUserRequest;
use App\Api\Users\Requests\ReadUserRequest;
use App\Api\Users\Requests\RegisterUserRequest;
use App\Api\Users\Requests\UpdateUserRequest;
use Carbon\Carbon;
use Dingo\Api\Routing\Helpers;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests, Helpers;

    private $userRepo;

    public function __construct(UserRepository $userRepo)
    {
        $this->userRepo = $userRepo;
    }

    public function index(ReadUserRequest $request)
    {
        $users = $this->userRepo->listAll();
        return $this->response->collection($users, new UserTransformer);
    }

    public function show($id, ReadUserRequest $request)
    {
        return $this->response->item($this->userRepo->find($id), new UserTransformer);
    }

    public function store(CreateUserRequest $request)
    {
        $data = $request->all();
        $data['password'] = Hash::make($data['password']);
        // TODO: put ID generation on API, not front end
        $user = $this->userRepo->create($request->json('id'), $data);
        return $this->response->item($user, new UserTransformer);
    }

    public function register(RegisterUserRequest $request)
    {
        $data = $request->all();
        $data['password'] = Hash::make($data['password']);
        $user = $this->userRepo->create($request->json('id'), $data);
        $user->groups()->attach(1);
        return $this->response->item($user, new UserTransformer);
    }

    public function update(UpdateUserRequest $request, $id)
    {
        $data = $request->all();
        if ($request->only('password')['password'])
            $data['password'] = Hash::make($data['password']);

        $user = $this->userRepo->update($id, $data);
        return $this->response->item($user, new UserTransformer);
    }

    public function destroy(DeleteUserRequest $request, $id)
    {
        $this->userRepo->delete($id);
        return $this->response->noContent();
    }

    public function addAvatar(CreateAvatarRequest $request, $id)
    {
        $image = $request->file('image');
        $filename = $id . Carbon::now()->format('dmygi') . '.' . $image->getClientOriginalExtension();
        if (!is_null(auth()->user()->avatar_filename))
            Storage::delete('public/images/avatars/' . $id. '/' . auth()->user()->avatar_filename);
        $imageDir = storage_path() . '/app/public/images/avatars/' . $id;
        $image->move($imageDir, $filename);
        $user = $this->userRepo->update($id, ['avatar_filename' => $filename]);
        return $this->response->item($user, new UserTransformer);
    }
}