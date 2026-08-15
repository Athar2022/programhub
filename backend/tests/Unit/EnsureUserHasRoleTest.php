<?php

namespace Tests\Unit;

use App\Http\Middleware\EnsureUserHasRole;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class EnsureUserHasRoleTest extends TestCase
{
    public function test_it_allows_a_user_with_the_required_role(): void
    {
        $user = new User([
            'role' => User::ROLE_PLATFORM_ADMIN,
        ]);
        $request = Request::create('/api/admin', 'GET');
        $request->setUserResolver(fn () => $user);
        $middleware = new EnsureUserHasRole();

        $response = $middleware->handle(
            $request,
            fn (Request $request): Response => response()->json([
                'message' => 'Allowed.',
            ]),
            User::ROLE_PLATFORM_ADMIN,
        );

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame(['message' => 'Allowed.'], $response->getData(true));
    }

    public function test_it_rejects_a_user_with_an_unmatched_role(): void
    {
        $user = new User([
            'role' => User::ROLE_APPLICANT,
        ]);
        $request = Request::create('/api/admin', 'GET');
        $request->setUserResolver(fn () => $user);
        $middleware = new EnsureUserHasRole();

        $response = $middleware->handle(
            $request,
            fn (Request $request): Response => response()->json([
                'message' => 'Allowed.',
            ]),
            User::ROLE_PLATFORM_ADMIN,
        );

        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $this->assertSame(['message' => 'Forbidden.'], $response->getData(true));
    }

    public function test_it_rejects_an_unauthenticated_request(): void
    {
        $request = Request::create('/api/admin', 'GET');
        $request->setUserResolver(fn () => null);
        $middleware = new EnsureUserHasRole();

        $response = $middleware->handle(
            $request,
            fn (Request $request): Response => response()->json([
                'message' => 'Allowed.',
            ]),
            User::ROLE_PLATFORM_ADMIN,
        );

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $this->assertSame(['message' => 'Unauthenticated.'], $response->getData(true));
    }
}
