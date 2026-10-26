/* Author: Alexander Pankratov <s.pankratoff@gmail.com> */
#include <stdio.h>
#include <stdlib.h>
#include <stdint.h>
#include <string.h>
#if defined(MADELINE_ARM_AES) && defined(__linux__)
#include <sys/auxv.h>
#include <asm/hwcap.h>
#endif
#if defined(MADELINE_ARM_AES)
#define mb_ige madeline_arm_ige
#elif defined(MADELINE_X86_AES)
#define mb_ige madeline_x86_ige
#endif
extern int mb_ige(const unsigned char **,unsigned char **,size_t,const unsigned char **,unsigned char **,size_t,int);
extern int madeline_ige_crypt(const unsigned char *,unsigned char *,size_t,const unsigned char *,unsigned char *,int);
static uint32_t seed=43;
static void fill(unsigned char *p,size_t n){for(size_t i=0;i<n;i++){seed^=seed<<13;seed^=seed>>17;seed^=seed<<5;p[i]=(unsigned char)seed;}}
int main(void){
#if defined(MADELINE_X86_AES)
 if(!__builtin_cpu_supports("aes"))return 77;
#elif defined(MADELINE_ARM_AES) && defined(__linux__)
 if(!(getauxval(AT_HWCAP)&HWCAP_AES))return 77;
#endif
 const size_t sizes[]={0,16,32,80,1024,4096,65536,1048576};
 unsigned char *in[40],*out[40],*expected[40],key[40][32],iv[40][32],state[40][32],refstate[40][32];
 const unsigned char *inputs[40],*keys[40];unsigned char *ivs[40];unsigned checks=0;
 for(unsigned i=0;i<40;i++){in[i]=malloc(1048576);out[i]=malloc(1048576);expected[i]=malloc(1048576);if(!in[i]||!out[i]||!expected[i])return 1;keys[i]=key[i];ivs[i]=state[i];}
 for(unsigned n=1;n<=40;n++)for(unsigned z=0;z<8;z++)for(int enc=0;enc<2;enc++){
  size_t size=sizes[z];if(n>16 && size>4096)continue;
  for(unsigned i=0;i<n;i++){fill(in[i],size);fill(key[i],32);fill(iv[i],32);memcpy(refstate[i],iv[i],32);if(!madeline_ige_crypt(in[i],expected[i],size,key[i],refstate[i],enc))return 2;}
  for(unsigned variant=0;variant<3;variant++){
   memcpy(state,iv,sizeof(state));
   for(unsigned i=0;i<n;i++){inputs[i]=in[i];if(variant==1){memcpy(out[i],in[i],size);inputs[i]=out[i];}}
   size_t split=variant==2?(size/32)*16:0;
   if(split){if(!mb_ige(inputs,out,split,keys,ivs,n,enc))return 3;for(unsigned i=0;i<n;i++){inputs[i]+=split;out[i]+=split;}}
   if(!mb_ige(inputs,out,size-split,keys,ivs,n,enc))return 3;
   if(split)for(unsigned i=0;i<n;i++)out[i]-=split;
   for(unsigned i=0;i<n;i++){if(memcmp(expected[i],out[i],size)||memcmp(refstate[i],state[i],32)){fprintf(stderr,"Mismatch width=%u size=%zu enc=%d variant=%u\n",n,size,enc,variant);return 4;}checks+=2;}
  }
 }
 for(unsigned i=0;i<40;i++){free(in[i]);free(out[i]);free(expected[i]);}
 printf("{\"kernel_checks\":%u,\"widths\":\"1..40\",\"status\":\"passed\"}\n",checks);
}
