<?php

namespace Linecore\Cms;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Request;
use Linecore\Cms\Libs\LaravelLogViewer;

class LogViewerController extends Controller
{
    protected $request;

    public function __construct()
    {
        $this->request = app('request');
    }

    public function index()
    {
        if ($this->request->input('l')) {
            LaravelLogViewer::setFile(Crypt::decrypt($this->request->input('l')));
        }

        if ($this->request->input('dl')) {
            return $this->download(LaravelLogViewer::pathToLogFile(Crypt::decrypt($this->request->input('dl'))));
        } elseif ($this->request->has('del')) {
            app('files')->delete(LaravelLogViewer::pathToLogFile(Crypt::decrypt($this->request->input('del'))));

            return $this->redirect($this->request->url());
        } elseif ($this->request->has('delall')) {
            foreach (LaravelLogViewer::getFiles(true) as $file) {
                app('files')->delete(LaravelLogViewer::pathToLogFile($file));
            }

            return $this->redirect($this->request->url());
        }

        $data = [
            'logs'         => LaravelLogViewer::all(),
            'files'        => LaravelLogViewer::getFiles(true),
            'current_file' => LaravelLogViewer::getFileName(),
        ];

        if ($this->request->wantsJson()) {
            return $data;
        }

        if (Request::ajax()) {
            return app('view')->make('admin::logs.index_ajax', $data);
        }

        return app('view')->make('admin::logs.index', $data);
    }

    private function redirect($to)
    {
        if (function_exists('redirect')) {
            return redirect($to);
        }

        return app('redirect')->to($to);
    }

    private function download($data)
    {
        if (function_exists('response')) {
            return response()->download($data);
        }

        // For laravel 4.2
        return app('\Illuminate\Support\Facades\Response')->download($data);
    }
}
