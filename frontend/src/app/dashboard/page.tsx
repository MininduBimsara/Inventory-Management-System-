"use client";

import { AppShell } from "@/components/AppShell";
import { RequireAuth } from "@/components/RequireAuth";
import { apiRequest } from "@/lib/api";
import { toErrorMessage } from "@/lib/format";
import { useEffect, useState } from "react";

type CountResponse = {
  meta: {
    total: number;
  };
};

type StatCard = {
  label: string;
  value: number;
};

export default function DashboardPage(): React.ReactElement {
  const [stats, setStats] = useState<StatCard[]>([]);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const load = async (): Promise<void> => {
      try {
        const [users, cupboards, places, items, borrows, logs] =
          await Promise.all([
            apiRequest<CountResponse>("/users"),
            apiRequest<CountResponse>("/cupboards"),
            apiRequest<CountResponse>("/places"),
            apiRequest<CountResponse>("/items"),
            apiRequest<CountResponse>("/borrows"),
            apiRequest<CountResponse>("/activity-logs"),
          ]);

        setStats([
          { label: "Users", value: users.meta.total },
          { label: "Cupboards", value: cupboards.meta.total },
          { label: "Places", value: places.meta.total },
          { label: "Items", value: items.meta.total },
          { label: "Borrows", value: borrows.meta.total },
          { label: "Audit Entries", value: logs.meta.total },
        ]);
      } catch (requestError) {
        setError(toErrorMessage(requestError));
      }
    };

    void load();
  }, []);

  return (
    <RequireAuth>
      <AppShell
        title="Dashboard"
        subtitle="Operational snapshot of the inventory system and final-audit readiness"
      >
        {error ? <p className="error-text">{error}</p> : null}

        <div className="metric-grid">
          {stats.map((stat, index) => (
            <article
              key={stat.label}
              className="metric-card reveal"
              style={{ animationDelay: `${index * 0.08}s` }}
            >
              <h3>{stat.label}</h3>
              <p>{stat.value}</p>
            </article>
          ))}
        </div>
      </AppShell>
    </RequireAuth>
  );
}
