<?php

namespace App\Exceptions;

use App\Traits\ApiResponse;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Handler extends ExceptionHandler
{
    use ApiResponse;
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        if ($exception instanceof ValidationException){
            return $this->convertValidationExceptionToResponse($exception, $request);
        }

        if($exception instanceof ModelNotFoundException){
            $model = strtolower(class_basename($exception->getModel()));
            return $this->errorResponse("There isn't instance of {$model} with the specified ID", 404);
        }

        if($exception instanceof AuthenticationException){
            $this->unauthenticated($request, $exception);
        }

        if($exception instanceof AuthorizationException){
            $this->errorResponse('You don\'t have permission to perform this action', 403);
        }

        if($exception instanceof NotFoundHttpException){
            $this->errorResponse('The specified URL couldn\'t be found', 404);
        }

        if($exception instanceof MethodNotAllowedHttpException){
            $this->errorResponse('The method specified in the request isn\'t valid', 405);
        }

        if($exception instanceof HttpException){
            $this->errorResponse($exception->getMessage(), $exception->getCode());
        }

        if($exception instanceof QueryException){
            $code = $exception->errorInfo[1];
            if($code == 547){
                return $this->errorResponse('The resource can\'t be permanently deleted because it is related to someone else', 409);
            }
        }

        if(config('app.debug')){
            return parent::render($request, $exception);
        }else{
            return $this->errorResponse('Unexpected error, try later', 500);
        }
    }
}
