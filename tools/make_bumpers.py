# WAHC 100.9 AutoHouse FM station IDs: a synthesized sting (noise riser, brass "Au-to-House-F-M" motif with bell
# sparkle, D-major chord hit + sub kick, pad bed ducked under the DJ) + a Piper DJ voice, all made here, no samples.
# Run with Piper's venv (needs numpy + piper):  ~/piper-tts/venv/bin/python tools/make_bumpers.py <outdir>
# Writes <slug>.wav (48 kHz stereo) + bumpers.json (dur, voice [start,end], caption text) for index.html's BUMPERS.
# Then master to the songs' -16 LUFS and encode (two-pass ffmpeg loudnorm, linear) to assets/radio/fm-<slug>.m4a.
import json, wave, numpy as np
from piper import PiperVoice
SR=48000; rng=np.random.default_rng(7)
import os, sys
OUT=(sys.argv[1] if len(sys.argv)>1 else os.path.dirname(os.path.abspath(__file__)))+'/'   # where the .wav files + bumpers.json go
LINES={  # spoken (for Piper) -> caption text
 'fresh-oil':"W.A.H.C., one hundred point nine. AutoHouse FM. Here's Fresh Oil.",
 'rotate-me':"AutoHouse FM, one hundred point nine. Keeping Wood Dale rolling, on all four corners. This is Rotate Me.",
 'need-an-alternator':"Every make. Every model. W.A.H.C., Wood Dale. Here's Need an Alternator.",
 'cabin-air-filter':"Breathe easy, Chicagoland. You're listening to W.A.H.C., AutoHouse FM. This is Cabin Air Filter.",
}
def caption(t):return t.replace('W.A.H.C.','WAHC').replace('one hundred point nine','100.9')
def hz(n): return 440*2**((n-69)/12)          # MIDI note -> Hz
def t_(d): return np.arange(int(d*SR))/SR
def env(n,a,r,hold=None):
    e=np.ones(n);A=max(1,int(a*SR));R=max(1,int(r*SR));e[:A]=np.linspace(0,1,A)
    if R<n:e[-R:]*=np.linspace(1,0,R)
    return e
def brass(f,d,bright=1.0,vib=True):
    t=t_(d);n=len(t);out=np.zeros(n)
    b=np.minimum(1,t/0.06)*bright*14+2                     # harmonics open up on the attack
    ph=2*np.pi*f*t+(0.004*np.sin(2*np.pi*5.5*t)*np.minimum(1,t/0.25)*2*np.pi*f/5.5 if vib else 0)
    for k in range(1,28):
        if k*f>SR/2.2:break
        out+=np.sin(k*ph)/k*np.exp(-(k-1)/b)
    return out*env(n,0.012,min(0.12,d*0.4))
def bell(f,d):
    t=t_(d);out=np.zeros(len(t))
    for r,a,dc in[(1,1,1.6),(2.0,.5,.9),(2.76,.35,.6),(5.4,.2,.3),(8.9,.12,.18)]:
        out+=a*np.sin(2*np.pi*f*r*t)*np.exp(-t/dc)
    return out*env(len(t),0.002,0.05)
def pad(notes,d):
    t=t_(d);out=np.zeros((len(t),2))
    for n in notes:
        for det,pan in[(-0.07,0.2),(0.07,0.8)]:
            f=hz(n+det);s=np.zeros(len(t))
            for k in range(1,9):s+=np.sin(2*np.pi*f*k*t+rng.random()*6.28)/k**1.6
            out[:,0]+=s*(1-pan);out[:,1]+=s*pan
    return out/len(notes)
def onepole_lp(x,cut):                                    # time-varying one-pole low-pass
    y=np.zeros_like(x);z=0.0;a=1-np.exp(-2*np.pi*np.asarray(cut)/SR)
    a=np.broadcast_to(a,x.shape)
    for i in range(len(x)):z+=a[i]*(x[i]-z);y[i]=z
    return y
def reverb(x,decay=0.9,wet=0.22):
    L=int(decay*2.2*SR);t=np.arange(L)/SR
    out=np.zeros((len(x)+L-1,2))
    for c in range(2):
        ir=rng.standard_normal(L)*np.exp(-t*6.9/decay/2.2*2.2/decay*decay);ir[:int(.012*SR)]=0;ir/=np.sqrt((ir**2).sum())
        n=1<<int(np.ceil(np.log2(len(x)+L)));y=np.fft.irfft(np.fft.rfft(x[:,c],n)*np.fft.rfft(ir,n),n)[:len(x)+L-1]
        out[:,c]=y
    dry=np.zeros_like(out);dry[:len(x)]=x
    return dry*(1-wet)+out*wet
def place(buf,sig,at,gain=1.0,pan=0.5):
    i=int(at*SR);j=min(len(buf),i+len(sig));s=sig[:j-i]
    if s.ndim==1:buf[i:j,0]+=s*gain*(1-pan)*2**.5;buf[i:j,1]+=s*gain*pan*2**.5
    else:buf[i:j]+=s*gain
def resample(x,src,dst):
    n=len(x);m=int(round(n*dst/src));X=np.fft.rfft(x);Y=np.zeros(m//2+1,complex);k=min(len(X),len(Y));Y[:k]=X[:k]
    return np.fft.irfft(Y,m)*m/n
def radio_voice(x):
    n=len(x);X=np.fft.rfft(x);f=np.fft.rfftfreq(n,1/SR)
    g=np.clip((f-70)/80,0,1)*(1+0.5*np.exp(-((f-3800)/1600)**2))*np.where(f>11000,np.exp(-(f-11000)/3000),1)
    y=np.fft.irfft(X*g,n);y/=np.abs(y).max()+1e-9
    return np.tanh(2.2*y)/np.tanh(2.2)                      # broadcast-style compression
voice_model=PiperVoice.load('/home/jsprandel/piper-tts/voices/en_US-ryan-high.onnx')
def tts(text):
    try:
        from piper import SynthesisConfig;cfg=SynthesisConfig(length_scale=0.9)
        chunks=list(voice_model.synthesize(text,syn_config=cfg))
    except Exception:chunks=list(voice_model.synthesize(text))
    a=np.concatenate([c.audio_float_array for c in chunks])
    nz=np.where(np.abs(a)>0.01)[0];a=a[max(0,nz[0]-200):nz[-1]+400]
    return radio_voice(resample(a,voice_model.config.sample_rate,SR))
T0=1.05;E=60/132/2                                         # riser length; eighth note at 132 bpm
meta={}
for slug,line in LINES.items():
    v=tts(line);VS=T0+5*E+0.35;VE=VS+len(v)/SR;D=VE+2.1
    buf=np.zeros((int(D*SR)+SR,2))
    # riser: noise swept open
    n=int(T0*SR);nz=rng.standard_normal(n);cut=300*(8000/300)**(np.linspace(0,1,n)**1.6)
    r=onepole_lp(nz,cut)*np.linspace(0,1,n)**2*0.55;place(buf,r,0,1,0.35);place(buf,np.roll(r,331),0,1,0.65)
    # logo motif: Au-to-HOUSE F-M (D5 E5 F#5 A5 D6), brass lead + bell sparkle
    for i,(note,dur) in enumerate([(74,E),(76,E),(78,2*E),(81,E),(86,4*E)]):
        at=T0+[0,1,2,4,5][i]*E
        place(buf,brass(hz(note),dur*0.95,1.0),at,0.33,0.45);place(buf,brass(hz(note-12),dur*0.95,0.6),at,0.16,0.55)
        place(buf,bell(hz(note+12),1.2),at,0.10,0.6)
    # chord hit on "M": D major add9 brass stab + sub kick + splash
    HIT=T0+5*E
    for note,pan in[(50,.5),(57,.4),(62,.6),(66,.35),(69,.65),(76,.5)]:place(buf,brass(hz(note),0.7,1.2,False)*np.exp(-t_(0.7)/0.35),HIT,0.13,pan)
    kt=t_(0.5);place(buf,np.sin(2*np.pi*(45+80*np.exp(-kt*25))*kt)*np.exp(-kt*7),HIT,0.6,0.5)
    sp=rng.standard_normal(int(1.2*SR));sp=sp-onepole_lp(sp,4000);place(buf,sp*np.exp(-t_(1.2)*4)*0.18,HIT,1,0.5)
    # pad bed under the DJ, ducked while he talks, fading out at the end
    pb=pad([50,57,62,66,69,76],D-HIT)*0.32;tp=t_(D-HIT);duck=np.ones(len(tp))
    vs,ve=VS-HIT,VE-HIT;duck-=0.55*np.clip(np.minimum((tp-vs+0.15)/0.15,(ve+0.3-tp)/0.3),0,1)
    fade=np.clip((D-HIT-tp)/1.6,0,1)*np.minimum(1,tp/0.08);pb*= (duck*fade)[:,None];place(buf,pb,HIT)
    place(buf,v,VS,0.62,0.5)
    buf=reverb(buf[:int(D*SR)],0.9,0.2)[:int((D+0.4)*SR)]
    buf=np.tanh(1.3*buf/np.abs(buf).max())/np.tanh(1.3)*0.89
    with wave.open(OUT+slug+'.wav','wb') as w:
        w.setnchannels(2);w.setsampwidth(2);w.setframerate(SR);w.writeframes((buf*32767).astype('<i2').tobytes())
    meta[slug]={'dur':round(len(buf)/SR,2),'voice':[round(VS,2),round(VE,2)],'text':caption(line)}
    print(slug,meta[slug])
json.dump(meta,open(OUT+'bumpers.json','w'),indent=1)
