<script lang="ts">
    import { onMount } from "svelte";
    import { fade, fly } from "svelte/transition";
    import { sheetsService } from "./services/google_sheets_service.svelte";
    import Loader from "./Loader.svelte";

    interface Props {
        onBack: () => void;
        selectedTeacher?: string;
    }

    let { onBack, selectedTeacher = "" }: Props = $props();
    let isLoading = $state(true);
    let rows: any[] = $state([]);
    let monthlyStats: Record<string, number> = $state({});
    let categoryStats: Record<string, number> = $state({});
    let totalRecords = $state(0);
    let topTeacher = $state({ name: "", count: 0 });

    const categories = [
        {
            id: "normal",
            label: "Trabajo Normal",
            color: "bg-blue-500",
            icon: "💼",
        },
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
        { id: "paro", label: "Paro / Huelga", color: "bg-red-700", icon: "🛑" },
        {
            id: "sabdomfest",
            label: "Sábados, Domingos y Festivos",
            color: "bg-slate-400",
            icon: "📅",
        },
    ];

    onMount(async () => {
        try {
            const result = await sheetsService.getRows();
            if (result.success && result.records) {
                rows = result.records.map(record => record.values); // Extract only the values
                processStats(rows);
            }
        } catch (error) {
            console.error("Error fetching stats:", error);
        } finally {
            isLoading = false;
        }
    });

    function processStats(data: any[]) {
        // Data structure: [Timestamp, Email, Docente, Mes, ...Days]
        // Usually row 0 is header. Assuming row[2] is teacher, row[3] is month.

        // Skip header if it contains "Docente" or "Nombre" or if Month is "MES"
        let cleanData = data.filter(
            (row) =>
                row[2] &&
                row[2] !== "Nombre Docente" &&
                row[3] &&
                row[3] !== "MES",
        );

        if (selectedTeacher) {
            cleanData = cleanData.filter((row) => row[2] === selectedTeacher);
        }

        totalRecords = cleanData.length;

        // Stats by Month
        const months: Record<string, number> = {};
        cleanData.forEach((row) => {
            const month = row[3];
            months[month] = (months[month] || 0) + 1;
        });
        monthlyStats = months;

        // Stats by Teacher
        const teachers: Record<string, number> = {};
        cleanData.forEach((row) => {
            const teacher = row[2];
            teachers[teacher] = (teachers[teacher] || 0) + 1;
        });

        // Stats by Category (Iterate through days - index 4 to end)
        const cats: Record<string, number> = {};
        cleanData.forEach((row) => {
            for (let i = 4; i < row.length; i++) {
                const cellValue = row[i];
                if (cellValue) {
                    // Check if cellValue matches a category ID
                    // Ensure whitespace trimming
                    const id = cellValue.trim();
                    if (categories.some((c) => c.id === id)) {
                        cats[id] = (cats[id] || 0) + 1;
                    }
                }
            }
        });
        categoryStats = cats;

        // Find top teacher
        let max = 0;
        let maxTeacher = "N/A";
        Object.entries(teachers).forEach(([name, count]) => {
            if (count > max) {
                max = count;
                maxTeacher = name;
            }
        });
        topTeacher = { name: maxTeacher, count: max };
    }

    // Helper for max value in monthly stats for bar chart scaling
    function getMaxMonthCount() {
        const values = Object.values(monthlyStats);
        return values.length ? Math.max(...values) : 1;
    }
</script>

<div
    class="fixed inset-0 z-50 bg-slate-50 overflow-y-auto"
    in:fade={{ duration: 300 }}
>
    {#if isLoading}
        <Loader show={true} message="Analizando datos..." />
    {:else}
        <div class="max-w-6xl mx-auto p-4 md:p-8 space-y-8">
            <!-- Header -->
            <div
                class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4"
            >
                <div>
                    <h1 class="text-3xl font-bold text-slate-900">
                        {selectedTeacher
                            ? "Estadísticas Docentes"
                            : "Panel Global"}
                    </h1>
                    <p class="text-slate-500">
                        {selectedTeacher
                            ? `Resumen detallado para: ${selectedTeacher}`
                            : "Resumen acumulado de todos los registros"}
                    </p>
                </div>
                <button
                    onclick={onBack}
                    class="px-6 py-2.5 bg-white border border-slate-200 text-slate-700 font-semibold rounded-xl hover:bg-slate-50 transition-colors shadow-sm"
                >
                    Volver al Registro
                </button>
            </div>

            <!-- KPI Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Card 1 -->
                <div
                    class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 relative overflow-hidden"
                >
                    <div class="absolute top-0 right-0 p-4 opacity-10">
                        <span class="text-6xl">📊</span>
                    </div>
                    <p
                        class="text-xs font-bold text-slate-400 uppercase tracking-widest"
                    >
                        Total Registros
                    </p>
                    <p class="text-4xl font-bold text-slate-900 mt-2">
                        {totalRecords}
                    </p>
                </div>

                <!-- Card 2 -->
                <div
                    class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 relative overflow-hidden"
                >
                    <div class="absolute top-0 right-0 p-4 opacity-10">
                        <span class="text-6xl">🏆</span>
                    </div>
                    <p
                        class="text-xs font-bold text-slate-400 uppercase tracking-widest"
                    >
                        Docente Más Activo
                    </p>
                    <p
                        class="text-lg font-bold text-slate-900 mt-2 line-clamp-1"
                        title={topTeacher.name}
                    >
                        {topTeacher.name}
                    </p>
                    <p class="text-sm text-slate-500">
                        {topTeacher.count} registros
                    </p>
                </div>
                <!-- Card 3 -->
                <div
                    class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 relative overflow-hidden"
                >
                    <div class="absolute top-0 right-0 p-4 opacity-10">
                        <span class="text-6xl">📅</span>
                    </div>
                    <p
                        class="text-xs font-bold text-slate-400 uppercase tracking-widest"
                    >
                        Meses Registrados
                    </p>
                    <p class="text-4xl font-bold text-slate-900 mt-2">
                        {Object.keys(monthlyStats).length}
                    </p>
                </div>
            </div>

            <!-- Category Stats Breakdown -->
            <div
                class="bg-white rounded-3xl shadow-lg border border-slate-100 overflow-hidden"
            >
                <div class="p-6 border-b border-slate-50">
                    <h2 class="text-xl font-bold text-slate-900">
                        Desglose por Tipo de Actividad
                    </h2>
                    <p class="text-sm text-slate-500 mt-1">
                        Conteo total de días por cada tipo de novedad
                    </p>
                </div>
                <div class="p-6">
                    <div
                        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4"
                    >
                        {#each categories as cat}
                            {#if categoryStats[cat.id]}
                                <div
                                    class="flex items-center gap-3 p-3 rounded-xl border border-slate-100 bg-slate-50/50"
                                >
                                    <div
                                        class="w-10 h-10 rounded-full flex items-center justify-center text-lg shadow-sm {cat.color.replace(
                                            'bg-',
                                            'bg-opacity-20 text-',
                                        )}"
                                    >
                                        <span class="drop-shadow-sm"
                                            >{cat.icon}</span
                                        >
                                    </div>
                                    <div>
                                        <p
                                            class="text-xs font-bold text-slate-500 uppercase"
                                        >
                                            {cat.label}
                                        </p>
                                        <p
                                            class="text-xl font-bold text-slate-900"
                                        >
                                            {categoryStats[cat.id]}
                                        </p>
                                    </div>
                                </div>
                            {/if}
                        {/each}
                    </div>
                </div>
            </div>

            <!-- Monthly Stats -->
            <div
                class="bg-white rounded-3xl shadow-lg border border-slate-100 overflow-hidden"
            >
                <div class="p-6 border-b border-slate-50">
                    <h2 class="text-xl font-bold text-slate-900">
                        Registros por Mes
                    </h2>
                </div>
                <div class="p-6 space-y-4">
                    {#each Object.entries(monthlyStats).sort((a, b) => b[1] - a[1]) as [month, count], i}
                        <div class="space-y-2">
                            <div
                                class="flex justify-between text-sm font-medium"
                            >
                                <span class="text-slate-700">{month}</span>
                                <span class="text-slate-900 font-bold"
                                    >{count}</span
                                >
                            </div>
                            <!-- Progress Bar -->
                            <div
                                class="h-3 w-full bg-slate-100 rounded-full overflow-hidden"
                            >
                                <div
                                    class="h-full bg-blue-500 rounded-full"
                                    style="width: {(count /
                                        getMaxMonthCount()) *
                                        100}%"
                                    in:fly={{
                                        x: -20,
                                        duration: 500,
                                        delay: i * 50,
                                    }}
                                ></div>
                            </div>
                        </div>
                    {/each}
                    {#if Object.keys(monthlyStats).length === 0}
                        <div class="text-center py-10 text-slate-400">
                            No hay datos suficientes para mostrar estadísticas.
                        </div>
                    {/if}
                </div>
            </div>
        </div>
    {/if}
</div>
