<div class="container offers">
    @php
        $spots = App\Models\Tenant\Promotion::where('apply_restaurant', 0)
            ->where('type', 'spots')
            ->where('status', 1)
            ->orderBy('description')
            ->take(4)
            ->get();
            
        $emptySpots = 4 - $spots->count();
        for ($i = 0; $i < $emptySpots; $i++) {
            $spots->push(new App\Models\Tenant\Promotion([
                'image' => 'promocion-1.png'
            ]));
        }
    @endphp

    <div class="row">
        @foreach($spots->take(2) as $index => $spot)
            <div class="col-6 mb-3 spot-img-container">
                @if($spot->item_id && $spot->image !== 'promocion-1.png')
                    <a href="/ecommerce/item/{{ $spot->item_id }}/{{ $spot->id }}">
                        <img class="image-offers" 
                             src="{{ Storage::url('uploads/promotions/'.$spot->image) }}" 
                             alt="image-spot-{{ $index + 1 }}" 
                             width="100%"/>
                    </a>
                @else
                    <img class="image-offers" 
                         src="{{ asset('images/promocion-1.png') }}" 
                         alt="image-spot-{{ $index + 1 }}" 
                         width="100%"/>
                @endif
            </div>
        @endforeach
    </div>
    <div class="row">
        @foreach($spots->slice(2)->take(2) as $index => $spot)
            <div class="col-6 mb-3 spot-img-container">
                @if($spot->item_id && $spot->image !== 'promocion-1.png')
                    <a href="/ecommerce/item/{{ $spot->item_id }}/{{ $spot->id }}">
                        <img class="image-offers" 
                             src="{{ Storage::url('uploads/promotions/'.$spot->image) }}" 
                             alt="image-spot-{{ $index + 3 }}" 
                             width="100%"/>
                    </a>
                @else
                    <img class="image-offers" 
                         src="{{ asset('images/promocion-1.png') }}" 
                         alt="image-spot-{{ $index + 3 }}" 
                         width="100%"/>
                @endif
            </div>
        @endforeach
    </div>
</div>