<?php

namespace Fast\Contracts\Support;

interface Responsable
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Fast\Http\Request  $request
     * @return \Fast\Http\Response
     */
    public function toResponse($request);
}
