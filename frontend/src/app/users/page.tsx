"use client";

import { AppShell } from "@/components/AppShell";
import { RequireAuth } from "@/components/RequireAuth";
import { apiRequest } from "@/lib/api";
import { toErrorMessage } from "@/lib/format";
import { useCallback, useEffect, useState } from "react";

type UserRow = {
  id: number;
  name: string;
  email: string;
  roles: { id: number; name: string }[];
};

type UserListResponse = {
  data: {
    data: UserRow[];
  };
};

export default function UsersPage(): React.ReactElement {
  const [rows, setRows] = useState<UserRow[]>([]);
  const [name, setName] = useState("Staff User");
  const [email, setEmail] = useState("staff@example.com");
  const [password, setPassword] = useState("password");
  const [role, setRole] = useState("Staff");
  const [message, setMessage] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);

  const loadUsers = useCallback(async (): Promise<void> => {
    try {
      const response = await apiRequest<UserListResponse>("/users");
      setRows(response.data.data);
    } catch (requestError) {
      setError(toErrorMessage(requestError));
    }
  }, []);

  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect
    void loadUsers();
  }, [loadUsers]);

  const handleCreateUser = async (
    event: React.FormEvent<HTMLFormElement>,
  ): Promise<void> => {
    event.preventDefault();
    setError(null);
    setMessage(null);

    try {
      await apiRequest<{ message: string }>("/users", {
        method: "POST",
        body: JSON.stringify({ name, email, password, role }),
      });

      setMessage("User created successfully.");
      await loadUsers();
    } catch (requestError) {
      setError(toErrorMessage(requestError));
    }
  };

  return (
    <RequireAuth>
      <AppShell
        title="User Management"
        subtitle="Create users and verify role allocations through the API"
      >
        <form className="panel-card form-grid" onSubmit={handleCreateUser}>
          <h3>Create User</h3>
          <input
            value={name}
            onChange={(event) => setName(event.target.value)}
            placeholder="Name"
            required
          />
          <input
            value={email}
            onChange={(event) => setEmail(event.target.value)}
            type="email"
            placeholder="Email"
            required
          />
          <input
            value={password}
            onChange={(event) => setPassword(event.target.value)}
            type="password"
            placeholder="Password"
            required
          />
          <select
            value={role}
            onChange={(event) => setRole(event.target.value)}
          >
            <option value="Staff">Staff</option>
            <option value="Admin">Admin</option>
          </select>
          <button type="submit">Create User</button>
        </form>

        {message ? <p className="success-text">{message}</p> : null}
        {error ? <p className="error-text">{error}</p> : null}

        <div className="panel-card">
          <h3>Current Users</h3>
          <table className="table-grid">
            <thead>
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Roles</th>
              </tr>
            </thead>
            <tbody>
              {rows.map((user) => (
                <tr key={user.id}>
                  <td>{user.id}</td>
                  <td>{user.name}</td>
                  <td>{user.email}</td>
                  <td>
                    {user.roles.map((item) => item.name).join(", ") || "-"}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </AppShell>
    </RequireAuth>
  );
}
