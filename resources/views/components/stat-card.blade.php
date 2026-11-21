@props(['label', 'count', 'icon' => 'doc', 'tint' => 'blue', 'subtitle' => null, 'href' => null])

{{-- Kept for backward compatibility: x-stat-card now renders the Phase 2
     x-kpi-card. New code should use <x-kpi-card> directly. --}}
<x-kpi-card :label="$label" :count="$count" :icon="$icon" :tint="$tint"
            :subtitle="$subtitle" :href="$href" {{ $attributes }} />
