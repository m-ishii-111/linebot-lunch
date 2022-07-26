<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MessageMst;
use App\Http\Requests\MessageMstPostRequest;

class MessageMstController extends Controller
{
    private $messageMst;

    public function __construct(MessageMst $messageMst)
    {
        $this->messageMst = $messageMst;
    }

    public function index()
    {
        $messages = $this->messageMst->getMessages();
        return view('edit', compact('messages'));
    }

    public function store(MessageMstPostRequest $request)
    {
        $inputs = $request->validated();
        $this->messageMst->upsertMessage($inputs);
        return redirect('/home')->with('flash_message', '登録しました！');
    }
}
