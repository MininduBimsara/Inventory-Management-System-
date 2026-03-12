"use client";

import { apiRequest } from "@/lib/api";
import { setToken, setUser, type SessionUser } from "@/lib/session";
import { toErrorMessage } from "@/lib/format";
import { useRouter } from "next/navigation";
import { useState } from "react";

type LoginResponse = {
  data: {
    access_token: string;
    user: SessionUser;
  };
};

export default function LoginPage(): React.ReactElement {
  const router = useRouter();
  const [email, setEmail] = useState("admin@ims.local");
  const [password, setPassword] = useState("password");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleSubmit = async (
    event: React.FormEvent<HTMLFormElement>,
  ): Promise<void> => {
    event.preventDefault();
    setLoading(true);
    setError(null);

    try {
      const response = await apiRequest<LoginResponse>("/auth/login", {
        method: "POST",
        body: JSON.stringify({
          email,
          password,
          device_name: "nextjs-frontend",
        }),
      });

      setToken(response.data.access_token);
      setUser(response.data.user);
      router.replace("/dashboard");
    } catch (requestError) {
      setError(toErrorMessage(requestError));
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="login-screen">
      <div className="hero-card">
        <h1>Inventory Management Final Layer</h1>
        <p>
          Sign in to manage stock movement, borrow flows, and audit evidence.
        </p>
      </div>

      <form className="login-card" onSubmit={handleSubmit}>
        <h2>Operator Login</h2>

        <label htmlFor="email">Email</label>
        <input
          id="email"
          type="email"
          required
          value={email}
          onChange={(event) => setEmail(event.target.value)}
        />

        <label htmlFor="password">Password</label>
        <input
          id="password"
          type="password"
          required
          value={password}
          onChange={(event) => setPassword(event.target.value)}
        />

        {error ? <p className="error-text">{error}</p> : null}

        <button type="submit" disabled={loading}>
          {loading ? "Signing in..." : "Sign In"}
        </button>
      </form>
    </div>
  );
}
