<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BlogRequest;
use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use function Nette\Utils\match;

class BlogController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $filter = $request->input('filter');
        $search = $request->input('search');

        try {
            // Get blogs with pagination
            $query = Blog::with(['likes' => function($query){
                $query->select('id', 'user_id', 'likeable_id');
            },'likes.user' => function ($q) {
                $q->select('id', 'name', 'email');
            }])->withCount('likes');

            /*Apply Filter */
            if ($filter === 'most_liked') {
                $query->orderByDesc('likes_count')->orderByDesc('created_at');
            } elseif ($filter === 'latest') {
                $query->orderByDesc('created_at');
            } else {
                $query->orderByDesc('created_at');
            }
            /*Apply Filter End*/

            /* multiple-field  Search Filter*/
            if (!empty($search) && is_array($search)) {
                foreach ($search as $key => $value) {
                    $query->where($key, 'like', '%' . $value . '%');
                }
            }
            /* multiple-field  Search Filter End*/
            $blogs = $query->orderBy('created_at', 'desc')->paginate($perPage)->appends($request->query());

            return response()->json([
                'success' => 200,
                'message' => 'Blog list fetched successfully',
                'data' => $blogs
            ]);
        } catch (\Exception $e) {
            Log::error('Blog list fetched', $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['status' => 401, 'message' => 'Oops...something went wrong. Please try again.']);
        }
    }

    public function store(Request $request, BlogRequest $req)
    {
        DB::beginTransaction();
        try {
            /*Validation From Request Through Name:BlogRequest.php*/
            $validate = $req->validated();

            /*handle Store and update Comman Data*/
            $obj = $this->handleData($request);

            $blog = Blog::create($obj);
            /*set Response Image Url Path from APi generate*/
            if (!empty($obj['image_url'])) {
                $blog->image = $obj['image_url'];
            }
            DB::commit();
            return response()->json(['status' => 200, 'msg' => 'Blog Created Successfully', 'data' => $blog]);

        } catch (\Throwable $e) {
            Log::error('Blog Create Successfully', $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            DB::rollBack();
            return response()->json(['status' => 401, 'message' => 'Oops...something went wrong. Please try again.']);
        }
    }

    public function update(Request $request, BlogRequest $req, $id)
    {
        DB::beginTransaction();
        try {
            $blog = Blog::find($id);
            /*Validation From Request Through Name:BlogRequest.php*/
            $validate = $req->validated();

            /*handle Store and update Comman Data*/
            $obj  = $this->handleData($request);

            $blog->update($obj);

            /*set Response Image Url Path from APi generate*/
            if (isset($request->image)) {
                $blog->image = $obj['image_url'];
            } else {
                if ($blog->image != null) {
                    $blog->image = env('APP_URL') . '/' . $blog->image;
                }
            }
            DB::commit();
            return response()->json(['status' => 200, 'msg' => 'Blog Updated Successfully', 'data' => $blog]);
        } catch (\Throwable $e) {
            Log::error('Blog update Successfully', $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            DB::rollBack();
            return response()->json(['status' => 401, 'message' => 'Oops...something went wrong. Please try again.']);
        }
    }

    public function delete(Request $request)
    {
        DB::beginTransaction();
        try {
            $blog = Blog::find($request->id);

            if ($blog) {
                $blog->delete();
            }
            DB::commit();
            return response()->json(['status' => 200, 'msg' => 'Blog Deleted Successfully']);
        } catch (\Exception $e) {
            Log::error('Blog Deleted Successfully', $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            DB::rollBack();
            return response()->json(['status' => 500, 'message' => 'Oops...something went wrong. Please try again.']);
        }
    }

    public function BlogLikeUnlikeToggle(Request $request)
    {
        DB::beginTransaction();
        try{
            /*optinal In case Future mein koi case agar ho toh */
            $validator = Validator::make($request->all(), [
                'action' => 'required|in:like,unlike',
            ], [
                'action.required' => 'Action is required',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors());
            }

            $blog = Blog::with(['likes' => function ($q) use ($user) {
                $q->where('user_id', Auth::id());
            }])->find($request->blogId);

            if (!$blog)
                return response()->json(['status' => 401, 'msg' => 'No Founded Blog']);

            $existing = $blog->likes->first();

            if ($request->action == 'unlike') {
                if ($existing) {
                    $existing->delete();
                    return response()->json(['message' => 'unliked']);
                }
                return response()->json(['message' => 'not liked yet']);
            }

            if ($request->action === 'like') {
                if (!$existing) {
                    $blog->likes()->create(['user_id' => $user]);
                    return response()->json(['message' => 'liked']);
                }
                return response()->json(['message' => 'already liked']);
            }
            DB::commit();
        }
        catch (\Exception $e){
            Log::error('Blog Not Liked',$e->getMessage(),['trace' => $e->getTraceAsString()]);
            DB::rollBack();
            return response()->json(['status' => 500, 'message' => 'Oops...something went wrong. Please try again.']);
        }
    }

    /*Reuse Function For Create and Update Blog*/
    public function handleData(Request $request)
    {
        $objData = [];
        $imageUrl = '';
        $path = '';
        if (isset($request->image) && $request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '.' . $image->getClientOriginalExtension();

            $path = $image->storeAs('images', $imageName, 'public');

            $imageUrl = Storage::disk('public')->url($path);
        }

        $objData = [
            'title' => $request->title,
            'description' => $request->description
        ];

        /*Storage Database only folder with Path and Url from Image*/
        if (isset($request->image)) {
            if (!empty($path)) {
                $objData['image'] = 'storage/' . $path;
            }
            if (!empty($imageUrl)) {
                $objData['image_url'] = $imageUrl;
            }
        }
        return $objData;
    }
}
