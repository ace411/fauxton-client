<?php

namespace Chemem\Fauxton;

class ErrorHandler
{
    public static function errorHandler($level, $message, $file, $line)
    {
        if (error_reporting() !== 0) {
			throw new \ErrorException($message, 0, $level, $file, $line);
		}
    }
    
    public static function exceptionHandler($exception)
    {
        echo json_encode([
            'class' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),            
            'message' => $exception->getMessage()
        ]);
    }
}