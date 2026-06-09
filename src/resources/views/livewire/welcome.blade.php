<div class="relative min-h-screen">
    <!-- Background Effects -->
    <div class="fixed inset-0 pointer-events-none z-0 grid-bg"></div>
    <canvas id="particle-canvas" class="fixed inset-0 w-full h-full pointer-events-none z-10"></canvas>
    <div class="fixed inset-0 pointer-events-none z-0 noise-overlay"></div>

    <!-- Content -->
    <div class="relative z-10 max-w-[960px] mx-auto px-8">

        <!-- Logo -->
        <div class="pt-10">
            <div class="flex flex-col items-center md:flex-row md:items-center gap-4 md:gap-2">
                <div class="w-16 h-16">
                    <img src="{{ asset('images/logo.svg') }}" alt="{{ config('app.name') }} home" class="w-full h-full object-contain">
                </div>
                <div class="text-5xl lg:text-7xl font-bold">
                    <span>{{ config('app.name') }}</span>
                </div>
            </div>
        </div>

        <!-- Hero -->
        <section class="pt-10 pb-10">
            <h1 class="font-extrabold leading-[0.92] tracking-tight mb-7">
                <span class="block text-5xl lg:text-7xl text-base-content animate-fade-in" style="animation-delay: 0.05s">Build.</span>
                <span class="block text-5xl lg:text-7xl text-primary animate-fade-in" style="animation-delay: 0.15s">Share.</span>
                <span class="block text-5xl lg:text-7xl text-base-content/20 animate-fade-in" style="animation-delay: 0.25s">Survive.</span>
            </h1>
            <p class="text-base text-base-content/50 max-w-[480px] mb-9 font-light animate-fade-in" style="animation-delay: 0.35s">
                The definitive library for Cataclysm mods, discover community creations, publish your own, and keep your loadout updated.
            </p>
            <div class="flex items-center justify-center md:justify-start gap-3.5 flex-wrap animate-fade-in" style="animation-delay: 0.45s">
                <a href="{{ route('project-search', \App\Models\ProjectType::first()) }}" class="btn-primary-animation inline-flex items-center gap-2 font-medium text-sm px-6 py-2.5 rounded bg-primary text-base-200 hover:brightness-110 hover:-translate-y-0.5 transition-all">
                    <x-icon name="search" class="w-4 h-4" />
                    Browse Mods
                </a>
                @if(auth()->check())
                    <a href="{{ route('platform.dashboard') }}" class="inline-flex items-center gap-2 font-medium text-sm px-6 py-2.5 rounded bg-transparent text-base-content/65 border border-base-content/15 hover:text-base-content hover:border-base-content/35 hover:-translate-y-0.5 transition-all">
                        Dashboard
                        <svg class="w-4 h-4" viewBox="0 0 20 20" fill="none"><path d="M4 10h12M12 6l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </a>
                @endif
                @if (!auth()->check())
                    <a href="{{ route('register') }}" class="inline-flex items-center gap-2 font-medium text-sm px-6 py-2.5 rounded bg-transparent text-base-content/65 border border-base-content/15 hover:text-base-content hover:border-base-content/35 hover:-translate-y-0.5 transition-all">
                        Create Account
                        <svg class="w-4 h-4" viewBox="0 0 20 20" fill="none"><path d="M4 10h12M12 6l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </a>
                @endif
            </div>
            @if (!auth()->check())
                <div class="mt-3 text-sm text-center md:text-left animate-fade-in" style="animation-delay: 0.5s">
                    <span class="text-base-content/40">Already have an account?</span>
                    <a href="{{ route('login') }}" class="text-primary hover:text-primary/80 transition-colors">Log in</a>
                </div>
            @endif
        </section>

        <!-- Features -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-px border border-base-content/10 rounded-lg overflow-hidden mb-12 animate-fade-in" style="animation-delay: 0.55s">
            <div class="bg-base-100/50 p-7 overflow-hidden">
                <div class="w-10 h-10 flex items-center justify-center bg-primary/10 rounded-md mb-4 text-primary">
                    <x-icon name="search" class="w-5 h-5" />
                </div>
                <h3 class="font-bold text-base tracking-wide text-base-content mb-2">Discover</h3>
                <p class="text-sm leading-relaxed text-base-content/40 font-light">Browse a curated library of mods across weapons, scenarios, tiles, and more.</p>
            </div>
            <div class="bg-base-100/50 p-7 md:border-l border-base-content/10 overflow-hidden">
                <div class="w-10 h-10 flex items-center justify-center bg-primary/10 rounded-md mb-4 text-primary">
                    <x-icon name="download" class="w-5 h-5" />
                </div>
                <h3 class="font-bold text-base tracking-wide text-base-content mb-2">Download</h3>
                <p class="text-sm leading-relaxed text-base-content/40 font-light">Grab the latest release from your favorite mods directly from the platform.</p>
            </div>
            <div class="bg-base-100/50 p-7 md:border-l border-base-content/10 overflow-hidden">
                <div class="w-10 h-10 flex items-center justify-center bg-primary/10 rounded-md mb-4 text-primary">
                    <x-icon name="upload" class="w-5 h-5" />
                </div>
                <h3 class="font-bold text-base tracking-wide text-base-content mb-2">Share</h3>
                <p class="text-sm leading-relaxed text-base-content/40 font-light">Publish your own creations and share them with the community.</p>
            </div>
        </div>

        <!-- Footer -->
        <footer class="flex flex-col md:flex-row items-center justify-between py-5 border-t border-base-content/10 animate-fade-in gap-4" style="animation-delay: 0.65s">
            <span class="text-xs text-base-content/22">&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</span>
            <div class="flex gap-4">
                <x-footer-links />
            </div>
        </footer>
    </div>
</div>

<script>
(function(){
    const canvas=document.getElementById('particle-canvas');
    if(!canvas) return;
    const ctx=canvas.getContext('2d');
    let W,H,particles=[];
    const COLORS=['35,38,169','55,58,190','25,28,149'];
    function resize(){W=canvas.width=canvas.parentElement.offsetWidth;H=canvas.height=canvas.parentElement.offsetHeight||600}
    function rand(a,b){return Math.random()*(b-a)+a}
    function mp(){
        const fe=Math.random()<.55?'bottom':'left';
        return{x:fe==='bottom'?rand(0,W):rand(-20,0),y:fe==='bottom'?H+rand(0,20):rand(H*.1,H),r:rand(1.5,4),speed:rand(.2,.55),angle:fe==='bottom'?rand(-Math.PI*.7,-Math.PI*.3):rand(-Math.PI*.35,-Math.PI*.05),alpha:0,life:0,maxLife:rand(260,500),color:COLORS[Math.floor(Math.random()*COLORS.length)]}
    }
    for(let i=0;i<45;i++){const p=mp();p.life=rand(0,p.maxLife);particles.push(p)}
    function step(){
        ctx.clearRect(0,0,W,H);
        particles.forEach((p,i)=>{
            p.life++;p.x+=Math.cos(p.angle)*p.speed;p.y+=Math.sin(p.angle)*p.speed;
            const pr=p.life/p.maxLife;
            if(pr<.08)p.alpha=(pr/.08)*.5;else if(pr>.88)p.alpha=((1-pr)/.12)*.5;else p.alpha=.5;
            ctx.beginPath();ctx.arc(p.x,p.y,p.r,0,Math.PI*2);
            ctx.fillStyle='rgba('+p.color+','+p.alpha+')';ctx.fill();
            if(p.life>=p.maxLife)particles[i]=mp()
        });
        requestAnimationFrame(step)
    }
    window.addEventListener('resize',resize);resize();step();
})();
</script>
