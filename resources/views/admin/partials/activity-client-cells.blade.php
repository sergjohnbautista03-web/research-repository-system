@php($client = $log->clientDetails())
<td class="ual-ip-cell">{{ $log->ip_address ?: 'Not recorded' }}</td>
<td class="ual-client-cell">
    <strong>{{ $client['device'] }}</strong>
    <small>OS: {{ $client['os'] }}</small>
    <small>Browser: {{ $client['browser'] }}</small>
</td>
