<?php

namespace App\Exceptions;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Illuminate\View\ViewException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontReport = [
        \Symfony\Component\Mailer\Exception\TransportException::class,
        \Spatie\Dropbox\Exceptions\BadRequest::class,
    ];

    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function render($request, Throwable $e)
    {
        // Capture headers from the original response if it exists
        $headers = [];
        if ($response = $this->prepareResponse($request, $e)) {
            $headers = $response->headers->all();
        }

        if (app()->environment('production') && $e instanceof ViewException) {
            return $this->withHeaders(response()->view('errors.500', [], 500), $headers);
        }

        if (!$request->inertia()) {

            if ($request->wantsJson() || $request->is('api/*')) {
                if ($e instanceof ValidationException) {
                    return response()->json([
                        'success' => false,
                        'error' => $e->getMessage(),
                        'errors' => $e->errors(),
                    ]);
                }

                if ($e instanceof QueryException) {
                    $this->report($e);

                    return response()->json([
                        'success' => false,
                        'error' => 'Database error occurred. Please try again later.'
                    ]);
                }

                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
            }

            if ($this->isHttpException($e)) {
                if ($e->getStatusCode() === 429) {
                    return $this->withHeaders(response()->view('errors.429', [], 429), $headers);
                }

                if ($e->getStatusCode() === 404) {
                    return $this->withHeaders(response()->view('errors.404', [], 404), $headers);
                }

                if ($e->getStatusCode() === 403) {
                    return $this->withHeaders(response()->view('errors.403', [], 403), $headers);
                }

                if ($e->getStatusCode() === 500) {
                    return $this->withHeaders(response()->view('errors.500', [], 500), $headers);
                }
            }
        }

        $response = parent::render($request, $e);

        if ($response->status() === 419) {
            return back()->with([
                'message' => 'The page expired, please try again.',
            ]);
        }

        return $response;
    }


    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            if (app()->bound('sentry')) {
                app('sentry')->captureException($e);
            }
        });
    }

    /**
     * Attach headers to the response.
     *
     * @param \Illuminate\Http\Response $response
     * @param array $headers
     * @return \Illuminate\Http\Response
     */
    protected function withHeaders($response, array $headers)
    {
        foreach ($headers as $key => $values) {
            foreach ($values as $value) {
                $response->headers->set($key, $value);
            }
        }

        return $response;
    }
}
