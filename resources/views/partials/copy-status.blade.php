@php($colours = ['available' => 'success', 'on_loan' => 'warning text-dark', 'on_hold' => 'info text-dark', 'lost' => 'dark', 'damaged' => 'danger'])
<span class="badge bg-{{ $colours[$status] ?? 'secondary' }}">{{ ucfirst(str_replace('_', ' ', $status)) }}</span>
