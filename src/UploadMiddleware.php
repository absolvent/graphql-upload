<?php

namespace GraphQL\Upload;

use Closure;
use GraphQL\Server\RequestError;
use Illuminate\Http\Request;

class FileUpload
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $contentType = $request->headers->get('Content-Type');

        if (starts_with($contentType, 'multipart/form-data;') OR $request->files->count()) {
            $this->validateParsedBody($request);
            $result = $this->parseUploadedFiles($request);
            $request->replace($result);
        }

        return $next($request);
    }


    private function parseUploadedFiles(Request $request)
    {
        $bodyParams = $request->toArray();
        if (!isset($bodyParams['map'])) {
            throw new RequestError('The request must define a `map`');
        }
        $map = json_decode($bodyParams['map'], true);
        $result = json_decode($bodyParams['operations'], true);

        foreach ($map as $fileKey => $locations) {
            foreach ($locations as $location) {
                $items = &$result;
                foreach (explode('.', $location) as $key) {
                    if (!isset($items[$key]) || !is_array($items[$key])) {
                        $items[$key] = [];
                    }
                    $items = &$items[$key];
                }

                $items = $request->file($fileKey);
            }
        }

        return $result;
    }

    private function validateParsedBody(Request $request): void
    {
        $bodyParams = $request->toArray();
        if (null === $bodyParams) {
            throw new \Exception(
                'request is expected to provide parsed body for "multipart/form-data" requests but got null'
            );
        }
        if (!is_array($bodyParams)) {
            throw new \Exception(
                'GraphQL Server expects JSON object or array, but got something else'
            );
        }
        if (empty($bodyParams)) {
            throw new \Exception(
                'request is expected to provide parsed body for "multipart/form-data" requests but got empty array'
            );
        }
    }
}
