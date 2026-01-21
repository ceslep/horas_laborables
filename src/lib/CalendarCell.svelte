<script lang="ts">
  import { fade } from "svelte/transition";

  interface Props {
    day: number;
    value: string;
    onSelect: (val: string) => void;
    categories: any[];
    isCurrentMonth?: boolean;
  }

  let {
    day,
    value,
    onSelect,
    categories,
    isCurrentMonth = true,
  }: Props = $props();

  let showMenu = $state(false);

  const selectedCategory = $derived(categories.find((c) => c.id === value));

  function handleSelect(id: string) {
    onSelect(id);
    showMenu = false;
  }
</script>

<div
  class="relative h-24 md:h-32 p-2 border border-slate-100 transition-all duration-300
    {isCurrentMonth
    ? 'bg-white hover:bg-slate-50'
    : 'bg-slate-50 opacity-40 pointer-events-none'}"
>
  <div class="flex justify-between items-start">
    <span
      class="text-xs font-bold {selectedCategory
        ? 'text-slate-900'
        : 'text-slate-300'}"
    >
      {day}
    </span>

    {#if isCurrentMonth}
      <button
        onclick={() => (showMenu = !showMenu)}
        class="w-6 h-6 rounded-full flex items-center justify-center hover:bg-slate-100 text-slate-300 hover:text-slate-600 transition-colors"
      >
        <span class="text-lg">+</span>
      </button>
    {/if}
  </div>

  {#if selectedCategory}
    <div
      class="mt-2 p-1.5 rounded-lg {selectedCategory.color} flex flex-col items-center justify-center text-center gap-1 shadow-sm"
      in:fade
    >
      <span class="text-lg">{selectedCategory.icon}</span>
      <span
        class="text-[9px] font-bold text-white leading-tight uppercase line-clamp-2"
      >
        {selectedCategory.label.split(" ")[1] || selectedCategory.label}
      </span>
    </div>
  {/if}

  {#if showMenu}
    <div
      class="absolute left-0 top-full mt-2 w-48 bg-white border border-slate-200 rounded-xl shadow-2xl z-50 p-2 space-y-1 max-h-60 overflow-y-auto custom-scrollbar"
      in:fade={{ duration: 150 }}
    >
      {#each categories as category}
        <button
          onclick={() => handleSelect(category.id)}
          class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition-colors"
        >
          <span>{category.icon}</span>
          <span class="flex-1 text-left">{category.label}</span>
          {#if value === category.id}
            <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
          {/if}
        </button>
      {/each}
      {#if value}
        <div class="h-px bg-slate-100 my-1"></div>
        <button
          onclick={() => handleSelect("")}
          class="w-full text-left px-3 py-2 text-[10px] text-red-500 hover:text-red-600 font-bold uppercase"
        >
          Quitar Registro
        </button>
      {/if}
    </div>
  {/if}
</div>

{#if showMenu}
  <button
    class="fixed inset-0 z-40 w-full h-full border-none bg-transparent cursor-default"
    onclick={() => (showMenu = false)}
    aria-label="Cerrar menú"
  ></button>
{/if}
