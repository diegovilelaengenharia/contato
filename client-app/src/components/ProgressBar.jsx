import { CheckCircle, Circle } from 'lucide-react';

const FASES = [
    "Protocolo e Autuação",
    "Análise Documental",
    "Vistoria Técnica In Loco",
    "Emissão de Laudos/Peças",
    "Tramitação e Aprovação",
    "Entrega Final/Habite-se"
];

export default function ProgressBar({ currentPhase, mode = 'light' }) {
    const currentPhaseIndex = FASES.indexOf(currentPhase);
    const progressPercent = Math.round(((currentPhaseIndex + 1) / FASES.length) * 100);

    // Theme Variables
    const isHero = mode === 'hero';
    const textColor = isHero ? 'text-white' : 'text-vilela-primary';
    const subTextColor = isHero ? 'text-white/80' : 'text-gray-400';
    const labelColor = isHero ? 'text-white/60' : 'text-vilela-subtle';
    const barBg = isHero ? 'bg-black/20' : 'bg-vilela-bg';
    const barFill = isHero ? 'bg-white' : 'bg-vilela-primary';
    const barShadow = isHero ? 'shadow-none' : 'shadow-lg shadow-vilela-primary/40';

    return (
        <div className="w-full">
            <div className="flex justify-between items-end mb-6">
                <div>
                    <h4 className={`text-sm font-bold uppercase tracking-wider mb-1 ${labelColor}`}>Status Atual</h4>
                    <p className={`text-2xl font-bold ${textColor}`}>{currentPhase}</p>
                </div>
                <div className="text-right">
                    <span className={`text-3xl font-bold ${textColor}`}>{progressPercent}%</span>
                    <span className={`text-xs block font-medium ${subTextColor}`}>CONCLUÍDO</span>
                </div>
            </div>

            {/* Bar */}
            <div className={`h-2.5 w-full rounded-full overflow-hidden mb-8 shadow-inner ${barBg}`}>
                <div
                    className={`h-full ${barFill} ${barShadow} transition-all duration-1000 ease-out relative`}
                    style={{ width: `${progressPercent}%` }}
                >
                    <div className="absolute top-0 left-0 w-full h-full bg-white/20 animate-pulse"></div>
                </div>
            </div>

            {/* Steps (Desktop) */}
            <div className="hidden md:flex justify-between relative px-2">
                {/* Connecting Line */}
                <div className={`absolute top-3.5 left-0 w-full h-0.5 -z-10 ${isHero ? 'bg-white/20' : 'bg-vilela-border'}`} />

                {FASES.map((fase, index) => {
                    const isCompleted = index <= currentPhaseIndex;
                    const isCurrent = index === currentPhaseIndex;

                    // Step Colors
                    let circleBg = isHero ? 'bg-white/10 border-white/30' : 'bg-white border-gray-300';
                    let iconColor = isHero ? 'text-white/30' : 'text-gray-300';

                    if (isCompleted) {
                        circleBg = isHero ? 'bg-white border-white shadow-md' : 'bg-vilela-primary border-vilela-primary';
                        iconColor = isHero ? 'text-vilela-primary' : 'text-white';
                    }

                    return (
                        <div key={index} className="flex flex-col items-center group w-1/6">
                            <div
                                className={`w-7 h-7 rounded-full flex items-center justify-center border-2 transition-all duration-300
                  ${circleBg}
                  ${isCurrent ? (isHero ? 'ring-4 ring-white/20 scale-110' : 'ring-4 ring-vilela-light scale-110') : ''}
                `}
                            >
                                {isCompleted ? (
                                    <CheckCircle size={14} className={iconColor} strokeWidth={3} />
                                ) : (
                                    <Circle size={8} className={iconColor} fill="currentColor" />
                                )}
                            </div>
                            <span className={`mt-3 text-[10px] text-center font-bold px-1 transition-colors uppercase tracking-tight
                ${isCurrent ? (isHero ? 'text-white scale-105' : 'text-vilela-primary scale-105') : (isHero ? 'text-white/40' : 'text-gray-400')}
              `}>
                                {fase}
                            </span>
                        </div>
                    )
                })}
            </div>

            {/* Mobile Steps (Compact) */}
            <div className={`md:hidden space-y-3 p-4 rounded-xl border ${isHero ? 'bg-white/10 border-white/10' : 'bg-gray-50 border-gray-100'}`}>
                <h5 className={`text-xs font-bold uppercase ${isHero ? 'text-white/50' : 'text-gray-400'}`}>Histórico de Etapas</h5>
                {FASES.map((fase, index) => {
                    if (index > currentPhaseIndex) return null; // Show only up to current
                    return (
                        <div key={index} className="flex items-center gap-3">
                            <CheckCircle size={14} className={isHero ? 'text-white' : 'text-vilela-primary'} />
                            <span className={`text-xs font-bold ${index === currentPhaseIndex ? (isHero ? 'text-white' : 'text-vilela-primary') : (isHero ? 'text-white/70' : 'text-gray-600')}`}>
                                {fase}
                            </span>
                        </div>
                    )
                })}
            </div>
        </div>
    )
}
