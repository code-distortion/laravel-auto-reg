BLADE TEMPLATE WITH THE LOT

@include('my-app1::blade-template1')
@include('my-app1::sub-dir1.sub-dir2.blade-template2')

<x-my-app1::anonymous-component1 />
<x-my-app1::sub-dir1.sub-dir2.anonymous-component2 />

<x-my-app1::view-component1 />
<x-my-app1::sub-dir1.sub-dir2.view-component2 />

<livewire:my-app1::livewire-component1 />
<livewire:my-app1::sub-dir1.sub-dir2.livewire-component2 />
