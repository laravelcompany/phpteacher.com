@extends("frontend.layouts.app")

@section("title")
    {{ app_name() }}
@endsection

@section("content")
    <section class="bg-white dark:bg-gray-800">
        <div class="mx-auto max-w-screen-xl px-4 py-24 text-center sm:px-12">
            <div class="m-6 flex justify-center">
                <img class="h-24 rounded" src="{{ asset("logo.svg") }}" alt="{{ app_name() }}" />
            </div>
            <h1
                class="mb-6 text-4xl leading-none font-extrabold tracking-tight text-gray-900 sm:text-6xl dark:text-white"
            >
                {{ app_name() }}
            </h1>
            <p class="mb-10 text-lg font-normal text-gray-500 sm:px-16 sm:text-2xl xl:px-48 dark:text-gray-400">
                {!! setting("app_description") !!}
            </p>
            <div class="mb-8 flex flex-col space-y-4 sm:flex-row sm:justify-center sm:space-y-0 sm:space-x-4 lg:mb-16">

                <a
                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-5 py-3 text-center text-base font-medium text-gray-900 hover:bg-gray-100 focus:ring-4 focus:ring-gray-100 dark:border-gray-700 dark:text-gray-700 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-800"
                    href="https://phpteacher.com"
                    target="_blank"
                >
                    <svg
                        class="icon icon-tabler icons-tabler-outline icon-tabler-world-www"
                        xmlns="http://www.w3.org/2000/svg"
                        width="24"
                        height="24"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path d="M19.5 7a9 9 0 0 0 -7.5 -4a8.991 8.991 0 0 0 -7.484 4" />
                        <path d="M11.5 3a16.989 16.989 0 0 0 -1.826 4" />
                        <path d="M12.5 3a16.989 16.989 0 0 1 1.828 4" />
                        <path d="M19.5 17a9 9 0 0 1 -7.5 4a8.991 8.991 0 0 1 -7.484 -4" />
                        <path d="M11.5 21a16.989 16.989 0 0 1 -1.826 -4" />
                        <path d="M12.5 21a16.989 16.989 0 0 0 1.828 -4" />
                        <path d="M2 10l1 4l1.5 -4l1.5 4l1 -4" />
                        <path d="M17 10l1 4l1.5 -4l1.5 4l1 -4" />
                        <path d="M9.5 10l1 4l1.5 -4l1.5 4l1 -4" />
                    </svg>
                    <span class="ms-2">Website</span>
                </a>
            </div>

            @include("frontend.includes.messages")
        </div>
    </section>

    <section class="bg-gray-100 py-20 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
        <div class="container mx-auto flex flex-col items-center justify-center px-5">
            <div class="w-full text-center lg:w-2/3">
                <h1 class="mb-4 text-3xl font-medium text-gray-800 sm:text-4xl dark:text-gray-200">
                    {{ __("Screenshots of the project") }}
                </h1>

                <p class="mb-8 leading-relaxed">
                    In the following section we listed a number of screenshots of different parts of the project,
                    Laravel Starter.
                </p>
            </div>
        </div>
    </section>

@endsection
