@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if (trim($slot) === 'HUB01 Shop')
<span><span>HUB01</span> Shop</span>
@else
{{ $slot }}
@endif
</a>
</td>
</tr>
