<script lang="ts">
  import { fade, fly } from "svelte/transition";
  import CalendarCell from "./CalendarCell.svelte";
  import TeacherSelector from "./TeacherSelector.svelte";
  import { sheetsService } from "./services/google_sheets_service.svelte";

  let email = $state("");
  let teacherName = $state("");
  let month = $state("");
  let hoursData = $state<Record<number, string>>({});
  let isSaving = $state(false);
  let saveStatus = $state<{
    type: "success" | "error";
    message: string;
  } | null>(null);

  const months = [
    "Enero",
    "Febrero",
    "Marzo",
    "Abril",
    "Mayo",
    "Junio",
    "Julio",
    "Agosto",
    "Septiembre",
    "Octubre",
    "Noviembre",
    "Diciembre",
  ];

  const categories = [
    { id: "normal", label: "Trabajo Normal", color: "bg-blue-500", icon: "💼" },
    {
      id: "ordinario",
      label: "Permiso Ordinario",
      color: "bg-green-500",
      icon: "📝",
    },
    {
      id: "sindical",
      label: "Permiso Sindical",
      color: "bg-purple-500",
      icon: "⚖️",
    },
    {
      id: "jurado",
      label: "Jurado de Votación",
      color: "bg-emerald-600",
      icon: "🗳️",
    },
    {
      id: "gobernacion",
      label: "Día Libre Gobernación",
      color: "bg-cyan-600",
      icon: "🏛️",
    },
    {
      id: "profesor",
      label: "Día del Profesor",
      color: "bg-rose-500",
      icon: "🍎",
    },
    {
      id: "calamidad",
      label: "Calamidad Doméstica",
      color: "bg-stone-500",
      icon: "🏠",
    },
    {
      id: "luto",
      label: "Licencia por Luto",
      color: "bg-slate-600",
      icon: "✝️",
    },
    {
      id: "medica",
      label: "Incapacidad Médica",
      color: "bg-red-500",
      icon: "🏥",
    },
    {
      id: "maternidad",
      label: "Licencia Mat./Pat.",
      color: "bg-teal-500",
      icon: "👶",
    },
    {
      id: "secretaria",
      label: "Permisos/Capacitaciones Secretaría",
      color: "bg-amber-500",
      icon: "🎓",
    },
    {
      id: "bienestar",
      label: "Bienestar Docente",
      color: "bg-pink-500",
      icon: "🧘",
    },
    {
      id: "pedagogica",
      label: "Jornada Pedagógica",
      color: "bg-violet-500",
      icon: "📓",
    },
    {
      id: "familia",
      label: "Día de la Familia",
      color: "bg-orange-500",
      icon: "👨‍👩‍👧‍👦",
    },
    {
      id: "cumpleaños",
      label: "Cumpleaños",
      color: "bg-yellow-500",
      icon: "🎂",
    },
    {
      id: "quinquenio",
      label: "Quinquenio",
      color: "bg-indigo-600",
      icon: "🏅",
    },
    {
      id: "vacaciones",
      label: "Vacaciones",
      color: "bg-sky-500",
      icon: "✈️",
    },
    {
      id: "paro",
      label: "Paro / Huelga",
      color: "bg-red-700",
      icon: "📢",
    },
    {
      id: "festivo",
      label: "Sábado, Dom o Festivo",
      color: "bg-slate-400",
      icon: "🏝️",
    },
  ];

  const currentYear = 2025;

  function getDaysInMonth(monthName: string) {
    const monthIndex = months.indexOf(monthName);
    if (monthIndex === -1) return 0;
    return new Date(currentYear, monthIndex + 1, 0).getDate();
  }

  function getStartDay(monthName: string) {
    const monthIndex = months.indexOf(monthName);
    if (monthIndex === -1) return 0;
    return new Date(currentYear, monthIndex, 1).getDay();
  }

  const daysInMonth = $derived(getDaysInMonth(month));
  const startDay = $derived(getStartDay(month));

  function handleSelect(day: number, category: string) {
    if (category === "") {
      delete hoursData[day];
    } else {
      hoursData[day] = category;
    }
  }

  const completedDays = $derived(Object.keys(hoursData).length);
  const totalDays = $derived(daysInMonth || 30);
  const progress = $derived((completedDays / totalDays) * 100);

  async function handleSubmit() {
    if (!email || !teacherName || !month) return;

    isSaving = true;
    saveStatus = null;

    try {
      const daysArray = Array.from({ length: 31 }, (_, i) => {
        const dayNum = i + 1;
        return hoursData[dayNum] || "";
      });

      const values = [email, teacherName, month, ...daysArray];
      const result = await sheetsService.appendRow(values);

      if (result.success) {
        saveStatus = {
          type: "success",
          message: result.message || "Información guardada con éxito",
        };
      } else {
        throw new Error(result.error || "Error al guardar");
      }
    } catch (error: any) {
      saveStatus = {
        type: "error",
        message: error.message || "No se pudo conectar con el servidor",
      };
    } finally {
      isSaving = false;
    }
  }

  const weekdays = ["Dom", "Lun", "Mar", "Mié", "Jue", "Vie", "Sáb"];
</script>

<div class="max-w-6xl mx-auto p-4 md:p-8 space-y-8" in:fade>
  <!-- Header Card -->
  <div
    class="relative overflow-hidden rounded-3xl bg-white border border-slate-200 p-8 shadow-xl"
  >
    <div
      class="absolute top-0 right-0 -mr-16 -mt-16 w-64 h-64 bg-purple-100 rounded-full blur-3xl opacity-50"
    ></div>
    <div
      class="absolute bottom-0 left-0 -ml-16 -mb-16 w-64 h-64 bg-blue-100 rounded-full blur-3xl opacity-50"
    ></div>

    <div class="relative z-10 space-y-6">
      <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
          <h1
            class="text-4xl font-bold bg-gradient-to-r from-slate-900 to-slate-600 bg-clip-text text-transparent"
          >
            Control de Horas Laboradas
          </h1>
          <p
            class="text-slate-400 font-bold tracking-widest uppercase text-xs mt-2"
          >
            INSTITUTO GUATICA • {currentYear}
          </p>
        </div>
        <div class="text-right">
          <div class="text-sm text-slate-400 mb-1">Días registrados</div>
          <div class="text-3xl font-mono font-bold text-slate-900">
            {completedDays} / {totalDays}
          </div>
        </div>
      </div>

      <!-- Progress Bar -->
      <div class="h-2 w-full bg-slate-100 rounded-full overflow-hidden">
        <div
          class="h-full bg-gradient-to-r from-blue-600 to-purple-600 transition-all duration-500 ease-out"
          style="width: {progress}%"
        ></div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="space-y-2">
          <label
            class="text-xs font-bold text-slate-500 uppercase tracking-tighter"
            for="email">Correo Electrónico</label
          >
          <input
            id="email"
            type="email"
            bind:value={email}
            placeholder="ejemplo@correo.com"
            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition-all"
          />
        </div>

        <div class="space-y-2">
          <label
            class="text-xs font-bold text-slate-500 uppercase tracking-tighter"
            for="teacher">Nombre Docente</label
          >
          <TeacherSelector id="teacher" bind:value={teacherName} />
        </div>

        <div class="space-y-2">
          <label
            class="text-xs font-bold text-slate-500 uppercase tracking-tighter"
            for="month">Mes</label
          >
          <select
            id="month"
            bind:value={month}
            onchange={() => (hoursData = {})}
            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-slate-900 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition-all appearance-none cursor-pointer"
          >
            <option value="" disabled selected>Seleccionar mes</option>
            {#each months as m}
              <option value={m}>{m}</option>
            {/each}
          </select>
        </div>
      </div>
    </div>
  </div>

  <!-- Calendar Grid Card -->
  <div
    class="bg-white border border-slate-200 rounded-3xl overflow-hidden shadow-xl"
  >
    <div class="p-6 border-b border-slate-100 bg-slate-50/50">
      <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold text-slate-900">
          {month ? `Calendario de ${month}` : "Seleccione un mes para comenzar"}
        </h2>
        <div class="hidden md:flex gap-4">
          {#each categories.slice(0, 3) as cat}
            <div
              class="flex items-center gap-2 text-[10px] text-slate-400 uppercase font-bold"
            >
              <span class="w-2 h-2 rounded-full {cat.color}"></span>
              {cat.label}
            </div>
          {/each}
        </div>
      </div>
    </div>

    {#if month}
      <div class="grid grid-cols-7 border-b border-slate-100 bg-slate-50/50">
        {#each weekdays as day}
          <div
            class="py-3 text-center text-[10px] font-bold text-slate-400 uppercase tracking-widest"
          >
            {day}
          </div>
        {/each}
      </div>

      <div class="grid grid-cols-7">
        {#each Array(startDay) as _}
          <CalendarCell
            day={0}
            value=""
            onSelect={() => {}}
            categories={[]}
            isCurrentMonth={false}
          />
        {/each}

        {#each Array(daysInMonth) as _, i}
          {@const dayNum = i + 1}
          <CalendarCell
            day={dayNum}
            value={hoursData[dayNum] || ""}
            onSelect={(val) => handleSelect(dayNum, val)}
            {categories}
          />
        {/each}

        {#each Array((7 - ((startDay + daysInMonth) % 7)) % 7) as _}
          <CalendarCell
            day={0}
            value=""
            onSelect={() => {}}
            categories={[]}
            isCurrentMonth={false}
          />
        {/each}
      </div>
    {:else}
      <div class="p-20 text-center space-y-4">
        <div class="text-4xl">📅</div>
        <p class="text-white/30 font-medium">
          Por favor seleccione un mes en el panel superior para habilitar el
          registro diario.
        </p>
      </div>
    {/if}

    <!-- Footer Action -->
    <div
      class="p-8 bg-slate-50 border-t border-slate-100 flex flex-col md:flex-row items-center justify-between gap-6"
    >
      <div class="flex flex-col gap-2">
        <div class="text-sm text-slate-500 max-w-md italic">
          Selecciona cada día para registrar tu actividad. Puedes hacer clic en
          el icono "+" para ver las opciones disponibles.
        </div>
        {#if saveStatus}
          <div
            class="text-sm font-bold {saveStatus.type === 'success'
              ? 'text-green-600'
              : 'text-red-600'}"
            in:fly={{ y: 10 }}
          >
            {saveStatus.message}
          </div>
        {/if}
      </div>
      <button
        onclick={handleSubmit}
        disabled={!email ||
          !teacherName ||
          !month ||
          completedDays === 0 ||
          isSaving}
        class="w-full md:w-auto px-10 py-4 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-2xl shadow-lg transition-all duration-300 disabled:opacity-30 disabled:cursor-not-allowed hover:scale-105 active:scale-95 flex items-center justify-center gap-2"
      >
        {#if isSaving}
          <svg
            class="animate-spin h-5 w-5 text-white"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
          >
            <circle
              class="opacity-25"
              cx="12"
              cy="12"
              r="10"
              stroke="currentColor"
              stroke-width="4"
            ></circle>
            <path
              class="opacity-75"
              fill="currentColor"
              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
            ></path>
          </svg>
          Guardando...
        {:else}
          Guardar Información
        {/if}
      </button>
    </div>
  </div>
</div>

<style>
  .custom-scrollbar::-webkit-scrollbar {
    width: 6px;
  }
  .custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
  }
  .custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.1);
    border-radius: 10px;
  }
  .custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(0, 0, 0, 0.2);
  }

  select option {
    background: white;
    color: #1a1a1a;
  }
</style>
