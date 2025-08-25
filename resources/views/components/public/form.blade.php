@props([
    'action',
    'method' => 'POST',
    'id' => null,
    'files' => false
])

<form 
    method="{{ $method === 'GET' ? 'GET' : 'POST' }}" 
    action="{{ $action }}" 
    @if($id) id="{{ $id }}" @endif
    @if($files) enctype="multipart/form-data" @endif
    {{ $attributes }}
>
    @csrf
    
    @if(!in_array(strtoupper($method), ['GET', 'POST']))
        @method($method)
    @endif
    
    {{ $slot }}
</form>