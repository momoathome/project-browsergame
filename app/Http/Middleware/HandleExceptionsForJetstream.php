<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class HandleExceptionsForJetstream
{
    public function handle(Request $request, Closure $next)
    {
        try {
            return $next($request);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Session::flash('jetstream.flash', [
                'banner' => 'Resource not found!',
                'bannerStyle' => 'danger',
            ]);
        } catch (\Illuminate\Auth\AuthenticationException $e) {
            Session::flash('jetstream.flash', [
                'banner' => 'You are not authenticated!',
                'bannerStyle' => 'danger',
            ]);
        } catch (\Exception $e) {
            Session::flash('jetstream.flash', [
                'banner' => 'An unexpected error occurred: ' . $e->getMessage(),
                'bannerStyle' => 'danger',
            ]);
        }
    
        return redirect()->back();
    }
    
}
