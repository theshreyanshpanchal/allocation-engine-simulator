@props(['current' => 'decisions'])

<nav class="subnav">
    <a href="{{ route('allocations.index') }}"
       class="{{ $current === 'decisions' ? 'subnav-link-active' : 'subnav-link-idle' }}">Decision history</a>
    <a href="{{ route('simulations.index') }}"
       class="{{ $current === 'runs' ? 'subnav-link-active' : 'subnav-link-idle' }}">Simulation runs</a>
</nav>
