<script lang="ts">
  import { fade, fly } from "svelte/transition";
  import Swal from "sweetalert2";
  import CalendarCell from "./CalendarCell.svelte";
  import TeacherSelector from "./TeacherSelector.svelte";
  import { sheetsService } from "./services/google_sheets_service.svelte";

  let email = $state("");
  let teacherName = $state("");
  let month = $state("");
  let hoursData = $state<Record<number, string>>({});
  let isSaving = $state(false);
  let saveStatus = $state<"saved" | "saving" | "error" | "idle">("idle");
  let saveTimeout: any;

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
      id: "sabdomfest",
      label: "Sábado, SDF o Festivo",
      color: "bg-slate-400",
      icon: "🏝️",
    },
  ];

  const currentYear = new Date().getFullYear();

  function getDaysInMonth(monthName: string) {
    const monthIndex = months.indexOf(monthName);
    if (monthIndex === -1) return 0;
    return new Date(currentYear, monthIndex + 1, 0).getDate();
  }

  function getStartDay(monthName: string) {
    const monthIndex = months.indexOf(monthName);
    if (monthIndex === -1) return 0;
    // (getDay() + 6) % 7 converts 0-6 (Sun-Sat) to 0-6 (Mon-Sun)
    return (new Date(currentYear, monthIndex, 1).getDay() + 6) % 7;
  }

  const daysInMonth = $derived(getDaysInMonth(month));
  const startDay = $derived(getStartDay(month));

  function handleSelect(day: number, category: string) {
    if (category === "") {
      delete hoursData[day];
    } else {
      hoursData[day] = category;
    }
    triggerAutoSave();
  }

  function triggerAutoSave() {
    if (!email || !teacherName || !month) return;

    saveStatus = "saving";
    if (saveTimeout) clearTimeout(saveTimeout);

    saveTimeout = setTimeout(async () => {
      try {
        const daysArray = Array.from({ length: 31 }, (_, i) => {
          const dayNum = i + 1;
          return hoursData[dayNum] || "";
        });

        const values = [email, teacherName, month, ...daysArray];
        const result = await sheetsService.appendRow(values);

        if (result.success) {
          saveStatus = "saved";
          setTimeout(() => {
            if (saveStatus === "saved") saveStatus = "idle";
          }, 3000);
        } else {
          saveStatus = "error";
        }
      } catch (error) {
        console.error("Auto-save error:", error);
        saveStatus = "error";
      }
    }, 1000);
  }

  const completedDays = $derived(Object.keys(hoursData).length);
  const totalDays = $derived(daysInMonth || 30);
  const progress = $derived((completedDays / totalDays) * 100);

  // Carga de datos existentes
  $effect(() => {
    if (teacherName && month) {
      loadExistingRecords();
    } else {
      hoursData = {};
    }
  });

  async function loadExistingRecords() {
    try {
      const result = await sheetsService.getRows();
      if (result.success && result.values) {
        // Buscar si ya existe un registro para este docente y mes
        // Estructura: [Timestamp, Email, Docente, Mes, D1, D2, ..., D31]
        // Docente está en index 2, Mes en index 3 (en la respuesta de getRows)
        // Nota: depende de cómo devuelve los datos el backend.
        // get_horas.php devuelve $response->getValues()

        const existingRow = result.values.find((row: any[]) => {
          return row[2] === teacherName && row[3] === month;
        });

        if (existingRow) {
          const loadedData: Record<number, string> = {};
          // Los días empiezan en el index 4
          for (let i = 1; i <= 31; i++) {
            const val = existingRow[i + 3];
            if (val) loadedData[i] = val;
          }
          hoursData = loadedData;
        } else {
          // No hay registro previo: marcar fines de semana
          const monthIndex = months.indexOf(month);
          const autoFilledData: Record<number, string> = {};
          if (monthIndex !== -1) {
            const daysInMonthCount = new Date(
              currentYear,
              monthIndex + 1,
              0,
            ).getDate();
            for (let d = 1; d <= daysInMonthCount; d++) {
              const date = new Date(currentYear, monthIndex, d);
              const dayOfWeek = date.getDay(); // 0 is Sunday, 6 is Saturday
              if (dayOfWeek === 0 || dayOfWeek === 6) {
                autoFilledData[d] = "sabdomfest";
              }
            }
          }
          hoursData = autoFilledData;
        }
      }
    } catch (error) {
      console.error("Error cargando registros:", error);
    }
  }

  const weekdays = ["Lun", "Mar", "Mié", "Jue", "Vie", "Sáb", "Dom"];
</script>

<div class="max-w-[1600px] mx-auto p-4 md:p-8" in:fade>
  <div class="flex flex-col lg:flex-row gap-8 items-start">
    <!-- Header Card (Sidebar on Desktop) -->
    <div class="w-full lg:w-[400px] lg:sticky lg:top-8 space-y-6">
      <div
        class="absolute top-0 right-0 -mr-16 -mt-16 w-64 h-64 bg-purple-100 rounded-full blur-3xl opacity-50"
      ></div>
      <div
        class="absolute bottom-0 left-0 -ml-16 -mb-16 w-64 h-64 bg-blue-100 rounded-full blur-3xl opacity-50"
      ></div>

      <div class="relative z-10 space-y-6">
        <div
          class="flex flex-col md:flex-row md:items-end justify-between gap-4"
        >
          <div>
            <h1
              class="text-4xl font-bold bg-gradient-to-r from-slate-900 to-slate-600 bg-clip-text text-transparent"
            >
              Control de Horas Laboradas
            </h1>
            <p
              class="text-slate-500 font-bold tracking-widest uppercase text-xs mt-2"
            >
              INSTITUTO GUATICA • {currentYear}
            </p>
          </div>
          <div class="text-right">
            <div class="text-sm text-slate-500 mb-1 font-medium">
              Días registrados
            </div>
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

        <div class="grid grid-cols-1 gap-5">
          <!-- Email Input -->
          <div class="space-y-2">
            <label
              class="text-[10px] font-extrabold text-slate-600 uppercase tracking-widest pl-1"
              for="email">Correo Electrónico</label
            >
            <div class="relative group">
              <div
                class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 group-focus-within:text-purple-600 transition-colors"
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  class="h-5 w-5"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"
                  />
                </svg>
              </div>
              <input
                id="email"
                type="email"
                bind:value={email}
                placeholder="ejemplo@correo.com"
                class="w-full bg-slate-50/50 border border-slate-300 rounded-2xl pl-11 pr-4 py-3 text-slate-900 placeholder:text-slate-500 focus:outline-none focus:ring-4 focus:ring-purple-500/10 focus:border-purple-500 focus:bg-white transition-all duration-300 shadow-sm hover:border-slate-400"
              />
            </div>
          </div>

          <!-- Teacher Selector -->
          <div class="space-y-2">
            <label
              class="text-[10px] font-extrabold text-slate-600 uppercase tracking-widest pl-1"
              for="teacher">Nombre Docente</label
            >
            <div class="relative group">
              <div
                class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-500 group-focus-within:text-purple-600 transition-colors z-10"
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  class="h-5 w-5"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                  />
                </svg>
              </div>
              <div class="relative">
                <TeacherSelector id="teacher" bind:value={teacherName} />
              </div>
            </div>
          </div>

          <!-- Month Selector -->
          <div class="space-y-2">
            <label
              class="text-[10px] font-extrabold text-slate-600 uppercase tracking-widest pl-1"
              for="month">Mes de Registro</label
            >
            <div class="relative group">
              <div
                class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-500 group-focus-within:text-purple-600 transition-colors"
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  class="h-5 w-5"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                  />
                </svg>
              </div>
              <select
                id="month"
                bind:value={month}
                class="w-full bg-slate-50/50 border border-slate-300 rounded-2xl pl-11 pr-10 py-3 text-slate-900 focus:outline-none focus:ring-4 focus:ring-purple-500/10 focus:border-purple-500 focus:bg-white transition-all duration-300 appearance-none cursor-pointer shadow-sm hover:border-slate-400"
              >
                <option value="" disabled selected>Seleccionar mes</option>
                {#each months as m}
                  <option value={m}>{m}</option>
                {/each}
              </select>
              <div
                class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-slate-500"
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  class="h-4 w-4"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M19 9l-7 7-7-7"
                  />
                </svg>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Calendar Grid Card (Main Content) -->
    <div
      class="flex-1 w-full bg-white border border-slate-200 rounded-3xl overflow-hidden shadow-xl"
    >
      <div class="p-6 border-b border-slate-100 bg-slate-50/50">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold text-slate-900">
            {month
              ? `Calendario de ${month}`
              : "Seleccione un mes para comenzar"}
          </h2>
          <div
            class="flex overflow-x-auto pb-2 md:pb-0 md:flex-wrap gap-x-4 gap-y-2 max-w-full md:max-w-2xl justify-start md:justify-end custom-scrollbar"
          >
            {#each categories as cat}
              <div
                class="flex items-center gap-2 text-[10px] text-slate-400 uppercase font-bold whitespace-nowrap min-w-fit"
              >
                <span class="w-2.5 h-2.5 rounded-full {cat.color} shrink-0"
                ></span>
                {cat.label}
              </div>
            {/each}
          </div>
        </div>
      </div>

      {#if month}
        <div
          class="hidden md:grid grid-cols-7 border-b border-slate-100 bg-slate-50/50"
        >
          {#each weekdays as day}
            <div
              class="py-3 text-center text-[10px] font-bold text-slate-400 uppercase tracking-widest"
            >
              {day}
            </div>
          {/each}
        </div>

        <div class="grid grid-cols-1 md:grid-cols-7">
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
            {@const weekdayIndex = (startDay + i) % 7}
            <CalendarCell
              day={dayNum}
              value={hoursData[dayNum] || ""}
              onSelect={(val) => handleSelect(dayNum, val)}
              {categories}
              weekday={weekdays[weekdayIndex]}
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
            Selecciona cada día para registrar tu actividad. Los cambios se
            guardan automáticamente.
          </div>
        </div>

        <div class="flex items-center gap-3">
          {#if saveStatus === "saving"}
            <div
              class="flex items-center gap-2 text-blue-600 font-bold text-xs uppercase tracking-widest"
              in:fade
            >
              <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24">
                <circle
                  class="opacity-25"
                  cx="12"
                  cy="12"
                  r="10"
                  stroke="currentColor"
                  stroke-width="4"
                  fill="none"
                ></circle>
                <path
                  class="opacity-75"
                  fill="currentColor"
                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                ></path>
              </svg>
              Sincronizando...
            </div>
          {:else if saveStatus === "saved"}
            <div
              class="flex items-center gap-2 text-emerald-600 font-bold text-xs uppercase tracking-widest"
              in:fade
            >
              <svg
                class="h-4 w-4"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="3"
                  d="M5 13l4 4L19 7"
                />
              </svg>
              Cambios Guardados
            </div>
          {:else if saveStatus === "error"}
            <div
              class="flex items-center gap-2 text-rose-600 font-bold text-xs uppercase tracking-widest"
              in:fade
            >
              <svg
                class="h-4 w-4"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="3"
                  d="M6 18L18 6M6 6l12 12"
                />
              </svg>
              Error al Guardar
            </div>
          {/if}
        </div>
      </div>
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
