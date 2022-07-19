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
        return view('edit', [
            'follow' => $messages['follow'][0] ?? '',
            'location' => $messages['location'][0] ?? '',
            'location_button' => $messages['location'][1] ?? '',
            'stamp' => $messages['stamp'][0] ?? '',
            'not_found' => $messages['location'][9] ?? '',
        ]);
    }

    public function store(MessageMstPostRequest $request)
    {
        $inputs = $request->validated();
        $this->messageMst->upsertMessage($inputs);
        return redirect('/home')->with('flash_message', '登録しました！');
    }
}
