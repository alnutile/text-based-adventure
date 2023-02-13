<template>
    <Head title="TextBasedAdventures.ai" />
                <div class="sm:text-center p-4">
                    <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">Text Based Adventures.ai</h2>
                    <div class="mx-auto mt-6 max-w-2xl text-lg text-rose-100">Start your adventure:</div>
                    <div class="flex mx-auto justify-center gap-4 mt-4">
                        <button type="button"
                                @click="setGenre('fantasy')"
                                :class="{'ring-2 ring-green-500 ring-offset-2' : genre === 'fantasy'}"
                                class="inline-flex items-center rounded border border-transparent bg-green-400
                                px-2.5 py-1.5 text-lg font-medium text-green-800 hover:bg-green-200
                                focus:outline-none
                                focus:ring-2 focus:ring-green-500 focus:ring-offset-2">Fantasy</button>
                        <button
                            :class="{'ring-2 ring-green-500 ring-offset-2' : genre === 'scifi'}"
                            @click="setGenre('scifi')"
                            type="button"
                                class="inline-flex items-center rounded border border-transparent bg-green-400
                                px-2.5 py-1.5 text-lg font-medium text-green-800 hover:bg-green-200
                                focus:outline-none
                                focus:ring-2 focus:ring-green-500 focus:ring-offset-2">Sci-Fi</button>
                        <Link :href="route('startOver')"
                            type="button"
                            class="inline-flex items-center rounded border border-transparent bg-green-400
                                px-2.5 py-1.5 text-lg font-medium text-green-800 hover:bg-green-200
                                focus:outline-none
                                focus:ring-2 focus:ring-green-500 focus:ring-offset-2">Start Over!</Link>
                    </div>
                </div>
                <form action="#" class="mt-2 sm:mx-auto sm:flex flex-col sm:max-w-lg mx-2"
                      @submit.prevent="submit">
                    <div class="min-w-0 flex-1">
                        <label for="tldr" class="sr-only">Choose your next step</label>
                        <div>
                            <div class="mt-1">
                                    <textarea
                                        autofocus
                                        required
                                        :disabled="running || !next_story_line"
                                        v-model="content"
                                        rows="5" name="content" id="content"
                                        placeholder="Your move..."
                                        class="
                                        disabled:bg-gray-300
                                        disabled:cursor-not-allowed
                                        block w-full rounded-t-lg border-gray-300 shadow-sm
                                        text-xl focus:border-green-500 focus:ring-green-500" />

                            </div>
                            <div class="bg-white p-2
rounded-b-lg text-gray-700 text-sm lowercase text-center
">Character Count: {{ characterCount }} limit {{ limit }}</div>
                        </div>
                    </div>
                    <div class="mt-4 sm:mt-2">
                        <button
                            :disabled="running  || !next_story_line"
                            type="submit"
                            class="
                                disabled:opacity-60
                                disabled:cursor-not-allowed
                                text-lg
                                block w-full rounded-md border border-transparent bg-gray-700 px-5 py-3
                                text-base font-medium text-white shadow hover:bg-black focus:outline-none
                                focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-green-500
                                sm:px-10 flex justify-center items-center">
                            <RunningDots v-if="running"/>
                            {{ submitLabel }}</button>
                    </div>
            </form>
        <div class="flex mx-auto">
            <div class="p-4 max-w-2xl w-full mx-auto text-green-700 text-xl">
                <div v-if="firstStoryRun">Building {{genre}} adventure back in a moment...</div>
                <div class="animate-pulse flex space-x-4 mt-4" v-if="running || firstStoryRun">
                    <div class="flex-1 space-y-6 py-1">
                        <div class="space-y-3">
                            <div class="grid grid-cols-3 gap-4">
                                <div class="h-4 bg-slate-700 rounded col-span-2"></div>
                                <div class="h-4 bg-slate-700 rounded col-span-1"></div>
                                <div class="h-4 bg-slate-700 rounded col-span-1"></div>
                                <div class="h-4 bg-slate-700 rounded col-span-1"></div>
                                <div class="h-4 bg-slate-700 rounded col-span-3"></div>
                                <div class="h-4 bg-slate-700 rounded col-span-2"></div>
                                <div class="h-4 bg-slate-700 rounded col-span-3"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex space-x-4 mt-4 text-3xl" v-if="!running && next_story_line">
                    {{ next_story_line }}
                </div>
            </div>
    </div>
</template>
<script>
import { Head, Link } from '@inertiajs/vue3';
import Footer from "@/Components/Footer.vue"
import RunningDots from "@/Components/RunningDots.vue"
import {useToast} from "vue-toastification";

export default {
    name: "Welcome",
    components: {
        Head,
        RunningDots,
        Footer,
        Link
    },
    props: ['prop_session_id'],
    mounted() {
        Echo.channel(`story.${this.session_id}`)
            .listen('.play', (e) => {
                this.firstStoryRun = false;
                this.next_story_line = e.next_story_line;
                this.previous = e.previous
                this.running = false
            })
    },
    data() {
        return {
            next_story_line: null,
            genre: null,
            session_id: this.prop_session_id,
            content: "",
            limit: 10000,
            waitingOnAi: true,
            firstStoryRun: false,
            running: false,
            toast: useToast(),
            error: null,
            previous: {}
        }
    },
    computed: {
        submitLabel() {
          if(this.genre === 'scifi') {
              return "Make it so!";
          }  else if(this.genre === 'fantasy') {
              return "Make my move";
          } else {
              return "Choose a Genre first..."
          }
        },
      characterCount() {
          return this.content !== null ? this.content.length : 0;
      }
    },
    methods: {
        setGenre(chosenGenre) {
            this.genre = chosenGenre;
            this.running = true;
            this.firstStoryRun = true;
            axios.post(route('startStory'), {
                genre: this.genre
            }).then(data => {

            })
                .catch(e => {
                    console.log(e.message);
                    if(e.message === 'Request failed with status code 422') {
                        this.toast.error(`Hmm keep the text to under ${this.limit} characters`)
                    } else {
                        this.error = "Looks like OpenAi API is having a moment..."
                    }

                    this.running = false;
                    this.firstStoryRun = false;
                });

        },
        submit() {
            this.running = true;
            axios.post(route("player"), {
                play: this.content
            }).then(data => {
                this.previous = [];
                this.content = "";
                this.next_story_line = null
            }).catch(e => {
                console.log(e.message);
                if(e.message === 'Request failed with status code 422') {
                    this.toast.error(`Hmm keep the text to under ${this.limit} characters`)
                } else {
                    this.error = "Looks like OpenAi API is having a moment..."
                }

                this.running = false;
            })
        }

    }

}

</script>
